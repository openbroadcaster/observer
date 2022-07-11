<?php

/* Parsing functions. The first thing we need to do is clean the content we
get from PHP files a bit. This includes removing empty lines (just in case there's
gaps between DocBlocks and start of class/method definitions), trimming all the
whitespace, and splitting the lines into an array. */

function parse_clean(string $content): array {
  $result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);
  $result = preg_split("/\r\n|\n|\r/", $result);
  foreach ($result as $i => $line) {
    $result[$i] = htmlspecialchars(trim($line));
  }

  return $result;
}

/* Convert the array of strings into an array containing all the start- and
endpoints of DocBlocks, as well as the line after the DocBlock containing the
class or method declaration. Note that since we've stripped empty lines, these
indices do *not* correspond to lines in the code. The characters at the end of
a DocBlock can also occur at the end of a regular multi-line comment, which is
why we're checking specifically for the next occurence of these characters after
the start of a DocBlock (which necessarily ends it). */

function parse_blocks(array $content): array {
  $blocks = array();

  $doc_start = array_keys($content, "/**");
  foreach ($doc_start as $start) {
    $end = array_values(array_filter(array_keys($content, "*/"), function ($value) use ($start) {
      return ($value > $start);
    }));

    if (empty($end)) {
      exit("[E] Parsing error: could not find ending */ for DocBlock starting with /**.");
    }

    if (!isset($content[$end[0] + 1])) {
      exit("[E] Parsing error: Method or class declaration could not be found under DocBlock.");
    }

    $doc = array_slice($content, $start + 1, $end[0] - $start - 1);
    foreach ($doc as $i => $line) {
      $doc[$i] = ltrim(ltrim($line, '*'));
    }

    $blocks[] = [
      'doc'   => $doc,
      'decl'  => $content[$end[0] + 1]
    ];
  }

  return $blocks;
}

/* Get the declaration type under the DocBlock. Using some simplifying assumptions,
we can check for the following strings: if it contains 'function', it's a class method;
if it contains 'class', it's a class (throwing an error if we find more than one in
a single file); otherwise, we assume it's a DocBlock description of the current file.
We return the type of declaration in an array with some additional information where
relevant. */

function parse_decl(string $decl): array {
  if (strpos($decl, "function") !== false) {

    $args = explode(",", trim(substr($decl, strpos($decl, "(") + 1, strpos($decl, ")") - strpos($decl, "(") - 1)));
    foreach ($args as $i => $arg) {
      $args[$i] = ltrim(trim($arg), "$");
    }

    return [
      'type'       => 'method',
      'visibility' => (strpos($decl, "private") !== false) ? "private" : ((strpos($decl, "protected") !== false) ? "protected" : "public"),
      'name'       => trim(explode("function", substr($decl, 0, strpos($decl, "(")))[1]),
      'args'       => $args
    ];
  } elseif (strpos($decl, "class") !== false) {
    return [
      'type'       => 'class',
      'name'       => explode(" ", trim(explode("class", $decl)[1]))[0]
    ];
  } else {
    return [
      'type'       => 'file'
    ];
  }
}

/* Parse the actual DocBlock. We're recognizing a small number of tags, which
can be used later when generating the HTML documentation. Note that parsing the
DocBlock is tag-unaware, which means that it may parse tags that never show up
in the actual documentation. */

function parse_doc(array $lines): array {
  $doc = [
    'description' => [],
    'tags'        => []
  ];

  $new_p = true;
  foreach ($lines as $line) {
    if ($line == "") {
      $new_p = true;
      continue;
    }

    if ($line[0] == '@') {
      $doc['tags'][] = [substr($line, 1, strpos($line, " ") - 1), substr($line, strpos($line, " ") + 1)];
    } else {
      if ($new_p) {
        $doc['description'][] = $line;
        $new_p = false;
      } else {
        $doc['description'][count($doc['description']) - 1] .= " " . $line;
      }
    }
  }

  return $doc;
}
