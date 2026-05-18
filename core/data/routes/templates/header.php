<!DOCTYPE HTML>
<html>
<head>
  <title><?=$title?></title>
  <meta charset="UTF-8">
  <link href="https://fonts.googleapis.com/css?family=Roboto|Roboto+Mono&display=swap" rel="stylesheet">
  <?php foreach ($styles as $style) { ?>
  <link rel="stylesheet" type="text/css" href="style/<?=$style?>?v=<?=filemtime(__DIR__ . '/../style/' . $style)?>">
  <?php } ?>
  <?php foreach ($scripts as $script) { ?>
  <script src="js/<?=$script?>?v=<?=filemtime(__DIR__ . '/../js/' . $script)?>"></script>
  <?php } ?>
</head>

<body>
  <header><?=$title?></header>
  <div class="doc-flex">
    <nav>
      <span>Routes</span>
      <a href="routes.html">Routes</a>
    <?php foreach ($nav_tree as $nav_package => $nav_items) { ?>
      <span><?=($nav_package == 'pages' ? 'Pages' : '@' . $nav_package)?></span>
      <?php
      foreach ($nav_items as $nav_item) {
        // Since all models in OB stick ~Model at the end, we want to strip this from
        // the nav links to make everything look a little cleaner.
        $nav_name = $nav_item;
        if (strpos($nav_name, "Model") === (strlen($nav_name) - 5)) {
          $nav_name = substr($nav_name, 0, strlen($nav_name) - 5);
        }
      ?>
        <a href="<?=$nav_package . '.' . $nav_item?>.html"><?=($nav_package == 'pages' ? '' : (strtolower($nav_package) . '.')) . $nav_name?></a>
      <?php } ?>
    <?php } ?>
    </nav>
    <main>
