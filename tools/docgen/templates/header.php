<!DOCTYPE HTML>
<html>
<head>
  <title><?=$title?></title>
  <meta charset="UTF-8">
  <link href="https://fonts.googleapis.com/css?family=Roboto|Roboto+Mono&display=swap" rel="stylesheet">
  <?php foreach ($styles as $style) { ?>
  <link rel="stylesheet" type="text/css" href="style/<?=$style?>">
  <?php } ?>
  <?php foreach ($scripts as $script) { ?>
  <script src="js/<?=$script?>"></script>
  <?php } ?>
</head>

<body>
  <header><?=$title?></header>
  <div class="doc-flex">
    <nav>
    <?php foreach ($nav_tree as $nav_package => $nav_items) { ?>
      <span><?=($nav_package == 'pages' ? 'Pages' : '@' . $nav_package)?></span>
      <?php foreach ($nav_items as $nav_item) { ?>
      <a href="<?=$nav_package . '.' . $nav_item?>.html"><?=($nav_package == 'pages' ? '' : (strtolower($nav_package) . '.')) . $nav_item?></a>
      <?php } ?>
    <?php } ?>
    </nav>
    <main>
