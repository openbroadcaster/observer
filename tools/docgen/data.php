<?php

/* DocGen data structures. Starts with a file at the top level (DocGenFile), which
contains a single class (DocGenClass) as well as metadata. Each class in the tree
can contain multiple private methods (DocGenMethod), as well as class metadata.
The array of methods is private to ensure correct typing. */

class DocGenFile {
  public $name;
  public $dir;
  public $description;

  private $class;

  public function __construct ($name, $dir, $description = []) {
    $this->name = $name;
    $this->dir  = $dir;
    $this->description = $description;

    $this->class = new DocGenClass(null);
  }

  public function getClass () : DocGenClass {
    return $this->class;
  }

  public function setClass (DocGenClass $class) {
    $this->class = $class;
  }
}

class DocGenClass {
  public $name;
  public $description;
  public $package;

  private $methods;

  public function __construct ($name, $description = [], $package = "NoPak") {
    $this->name = $name;
    $this->description = $description;
    $this->package = $package;

    $this->methods = array();
  }

  public function getMethods () {
    return $this->methods;
  }

  public function addMethod (DocGenMethod $method) {
    $this->methods[] = $method;
  }
}

class DocGenMethod {
  public $name;
  public $description;
  public $visibility;
  public $param;
  public $return;
  public $route;
  public $args;

  public function __construct ($name, $description = [], $visibility = "public", $args = [], $param = [], $return = "", $route = []) {
    $this->name = $name;
    $this->description = $description;
    $this->visibility = $visibility;
    $this->args = $args;
    $this->param = $param;
    $this->return = $return;
    $this->route = $route;
  }
}

/* Generate the object tree we'll need to generate the actual HTML documentation.
Using previously determined blocks (arrays of decls and docs), and the name of the
file, figure out all the classes and methods and their documentation. */

function generate_tree (array $blocks, string $filename, string $dir) : DocGenFile {
  $doc_file    = new DocGenFile($filename, $dir);
  $doc_class   = null;
  $doc_methods = [];

  foreach ($blocks as $block) {
    $decl = parse_decl($block['decl']);
    $doc  = parse_doc($block['doc']);

    switch ($decl['type']) {
      case 'file':
        $doc_file->description = array_merge($doc_file->description, $doc['description']);
      break;
      case 'class':
        if ($doc_class != null) {
          echo "[W] Multiple class definitions are not allowed in a single file: " . $doc_class->name . "\n";
          continue 2;
        }

        $doc_class = new DocGenClass($decl['name'], $doc['description']);

        foreach ($doc['tags'] as $tag) {
          switch ($tag[0]) {
            case 'package':
              $doc_class->package = $tag[1];
            break;
            default:
              echo "[W] Unsupported tag found: @" . $tag[0] . " (" . $tag[1] . ")\n";
            break;
          }
        }
      break;
      case 'method':
        $method = new DocGenMethod($decl['name'], $doc['description'], $decl['visibility'], $decl['args']);

        foreach ($doc['tags'] as $tag) {
          switch ($tag[0]) {
            case 'param':
              if (strpos($tag[1], " ") !== false) {
                $method->param[] = [substr($tag[1], 0, strpos($tag[1], " ")), substr($tag[1], strpos($tag[1], " ") + 1)];
              } else {
                $method->param[] = [$tag[1], ""];
              }
            break;
            case 'return':
              $method->return = $tag[1];
            break;
            case 'route':
              $method->route = [substr($tag[1], 0, strpos($tag[1], " ")), '/api' . substr($tag[1], strpos($tag[1], " ") + 1)];
            break;
            default:
              echo "[W] Unsupported tag found: @" . $tag[0] . " (" . $tag[1] . ")\n";
            break;
          }
        }

        $doc_methods[] = $method;
      break;
    }
  }

  if ($doc_class != null) {
    foreach ($doc_methods as $method) {
      $doc_class->addMethod($method);
    }
    $doc_file->setClass($doc_class);
  }

  return $doc_file;
}
