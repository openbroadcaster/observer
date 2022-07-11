<html>
<head>
  <meta charset="utf-8">
</head>
<body>

<h1>OpenBroadcaster Translate</h1>

<?php

$tmp = scandir('../strings/default');
$files = array();
foreach($tmp as $file)
{
  if(preg_match('/\.txt$/', $file)) $files[] = $file;
}

$input = '';

foreach($files as $file)
{

  $contents = explode("\n", file_get_contents('../strings/default/'.$file));
  $namespace = false;

  foreach($contents as $line)
  {

    // ignore empty lines
    $line = trim($line);
    if($line=='') continue;

    $line_split = preg_split("/(?<!\\\):/", $line, 2);

    // make sure we have a namespace, otherwise gets ignored by OB. see UIModel::strings().
    if(count($line_split)==1)
    {
      $namespace = true;
    }

    elseif(count($line_split)>=2 && $namespace)
    {
      $input.=trim($line_split[1])."\n";
    }
  }
}

$input = trim($input);

if(!empty($_POST['submit']))
{
  $error = false;

  $t = explode("\n", trim($_POST['t']));
  if(count($t)!=count(explode("\n", $input))) $error = 'The number of lines in the output (translation) must match the number of lines in the input.';

  // echo count($t).' '.count(explode("\n",$input));

  if($error) echo '<p style="color: #a00; font-weight: bold;">'.$error.'</p>';

  else
  {

    $index = 0;

    foreach($files as $file)
    {

      $output = array();

      $contents = explode("\n", file_get_contents('../strings/default/'.$file));
      $namespace = false;

      foreach($contents as $line)
      {

        // ignore empty lines
        $line = trim($line);
        if($line=='') { $output[] = ''; continue; }

        $line_split = preg_split("/(?<!\\\):/", $line, 2);

        // make sure we have a namespace, otherwise gets ignored by OB. see UIModel::strings().
        if(count($line_split)==1)
        {
          $namespace = true;
          $output[] = $line_split[0];
        }

        elseif(count($line_split)>=2 && $namespace!==false)
        {
          $output[]='  '.$line_split[0].': '.trim($t[$index]);
          $index++;
        }
      }

      $fh = fopen('translate/'.$file, 'wb+');
      // fwrite($fh,"\xef\xbb\xbf");
      // fwrite($fh,"\xEF\xBB\xBF".utf8_encode(implode("\n",$output)));
      fwrite($fh, implode("\n", $output));
      fclose($fh);
    }

    echo '<p style="font-weight: bold;">Translation complete. Copy tools/translate/* to the strings language directory.</p>';

  }
}
?>

<p>Translate all strings to another language using a translation tool (like Google Translate). Translation probably won't amazing, but it's nice for demoing or a starting point.</p>

<h2>Input</h2>

<p>Copy all text and paste into translator.</p>

<p>
<textarea readonly style="width: 600px; height: 400px;" wrap="off"><?=htmlspecialchars($input)?></textarea>
</p>

<h2>Output</h2>

<p>Paste output here, and then click submit. Translated files will be placed in the tools/translate directory.</p>

<form method="post">

<p>
<textarea name="t" style="width: 600px; height: 400px;" wrap="off"><?php if($_POST['t']) echo htmlspecialchars(trim($_POST['t'])); ?></textarea>
</p>

<p><input type="submit" value="Submit" name="submit"></p>

</form>
</body>
</html>
