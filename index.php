<?php
/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

// used by install checker to check whether this is an OB application index file.
// might be used by other things as well.
header('OpenBroadcaster-Application: index');

require_once('components.php');

if (is_file('VERSION')) {
    $version = trim(file_get_contents('VERSION'));
} else {
    $version = false;
}

// are we logged in? if not, redirect to welcome page.
$user = OBFUser::get_instance();
if (!isset($_COOKIE['ob_auth_id']) || !isset($_COOKIE['ob_auth_key']) || !$user->auth($_COOKIE['ob_auth_id'], $_COOKIE['ob_auth_key'])) {
    header('Location: /welcome');
    die();
}

// we're logged in! continue with load.
$models = OBFModels::get_instance();
$js_files    = $models->ui('js_files');
$css_files   = $models->ui('css_files');
$image_files = $models->ui('image_files');


$js_dependencies = [
  'node_modules/jquery/dist/jquery.min.js',
  'node_modules/jquery-migrate/dist/jquery-migrate.min.js'
]

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>OpenBroadcaster</title>

<?php
foreach ($js_dependencies as $file) {
    echo '<script type="text/javascript" src="' . $file . '?v=' . filemtime($file) . '"></script>' . PHP_EOL;
}
?>
  <script type="text/javascript" src="extras/jquery-ui.min.js?v=<?=filemtime('extras/jquery-ui.min.js')?>"></script>
  <script type="text/javascript" src="extras/jquery-ui-timepicker-addon.js?v=<?=filemtime('extras/jquery-ui-timepicker-addon.js')?>"></script>
  <script type="text/javascript" src="extras/jquery.ba-dotimeout.min.js?v=<?=filemtime('extras/jquery.ba-dotimeout.min.js')?>"></script>
  <script type="text/javascript" src="extras/jquery.json.js?v=<?=filemtime('extras/jquery.json.js')?>"></script>
  <script type="text/javascript" src="extras/jquery.DOMWindow.js?v=<?=filemtime('extras/jquery.DOMWindow.js')?>"></script>
  <script type="text/javascript" src="extras/jquery.scrollTo.min.js?v=<?=filemtime('extras/jquery.scrollTo.min.js')?>"></script>
  <script type="text/javascript" src="extras/jquery.visible.min.js?v=<?=filemtime('extras/jquery.visible.min.js')?>"></script>
  <script type="text/javascript" src="extras/jquery.mousewheel.min.js?v=<?=filemtime('extras/jquery.mousewheel.min.js')?>"></script>
  <script type="text/javascript" src="extras/jquery.contextMenu.js?v=<?=filemtime('extras/jquery.contextMenu.js')?>"></script>
  <script type="text/javascript" src="extras/dateformat.js?v=<?=filemtime('extras/dateformat.js')?>"></script>
  <script type="text/javascript" src="extras/moment.min.js?v=<?=filemtime('extras/moment.min.js')?>"></script>
  <script type="text/javascript" src="extras/moment.parseformat.js?v=<?=filemtime('extras/moment.parseformat.js')?>"></script>
  <script type="text/javascript" src="extras/parseduration.js?v=<?=filemtime('extras/parseduration.js')?>"></script>
  <script type="text/javascript" src="extras/tinymce/js/tinymce/tinymce.min.js?v=<?=filemtime('extras/tinymce/js/tinymce/tinymce.min.js')?>"></script>

  <script type="text/javascript" src="extras/simplebar/simplebar.min.js?v=<?=filemtime('extras/simplebar/simplebar.min.js')?>"></script>
  <link rel="stylesheet" type="text/css" href="extras/simplebar/simplebar.min.css?v=<?=filemtime('extras/simplebar/simplebar.min.css')?>">

  <link type="text/css" href="extras/opensans/opensans.css?v=<?=filemtime('extras/opensans/opensans.css')?>" rel="stylesheet">
  <link type="text/css" href="extras/jquery-ui-darkness/jquery-ui.min.css?v=<?=filemtime('extras/jquery-ui-darkness/jquery-ui.min.css')?>" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="extras/jquery-ui-timepicker-addon.css?v=<?=filemtime('extras/jquery-ui-timepicker-addon.css')?>">

  <script>
  OB_API_REWRITE = false;
  jQuery.ajax({
      url: '/api/v2/ping',
      success: function (result) {
        if(result=='"pong"') OB_API_REWRITE = true;
      },
      async: false
  });
  </script>

  <?php foreach ($js_files as $file) { ?>
    <script type="text/javascript" src="<?=$file?>?v=<?=filemtime($file)?>"></script>
  <?php } ?>

  <?php /* TODO should have a "last updated" time for strings */ ?>
  <script type="text/javascript" src="strings.php?v=<?=time()?>"></script>

  <?php foreach ($css_files as $file) { ?>
    <link rel="stylesheet" type="text/css" href="<?=$file?>?v=<?=filemtime($file)?>">
  <?php } ?>

  <?php if (!empty($user->userdata['dyslexia_friendly_font'])) { ?>
    <link rel="stylesheet" type="text/css" href="extras/opendyslexic/opendyslexic.css?v=<?=urlencode($version)?>">
  <?php } ?>

  <link rel="stylesheet" type="text/css" href="extras/fontawesome-free-5.15.3-web/css/all.css?v=<?=filemtime('extras/fontawesome-free-5.15.3-web/css/all.css')?>">

</head>

<body class="font-<?=(!empty($user->userdata['dyslexia_friendly_font']) ? 'opendyslexic' : 'default')?>">

<div id="main_container"></div>

<div id="preload_images" style="display: none;">
  <?php foreach ($image_files as $file) { ?>
    <img src="<?=$file?>?v=<?=filemtime($file)?>">
  <?php } ?>
</div>

</body>
</html>
