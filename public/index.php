<?php
// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// used by install checker to check whether this is an OB application index file.
// might be used by other things as well.
header('OpenBroadcaster-Application: index');

require_once(__DIR__ . '/../core/init.php');

$req = $_SERVER['REQUEST_URI'];
if ($req !== '/') {
  $req = strtok($req, '?');
  $req = ltrim($req, '/');

  // Check if module, and serve appropriate module file if so; resolve full path first
  // to prevent directory traversal attacks.
  if (str_starts_with($req, 'modules/')) {
    $basepath = realpath(__DIR__ . '/../modules/');
    $reqFile = $basepath . '/' . substr($req, 8);
    $path = realpath($reqFile);

    if ($path && str_starts_with($path, $basepath . DIRECTORY_SEPARATOR) && is_file($path)) {
      // Some old modules may directly call to php files (this is very bad practice).  If so,
      // simply include them and then die.
      $ext = pathinfo($path, PATHINFO_EXTENSION);
      if ($ext === 'php') {
        include_once($path);

        die();
      }

      // If no PHP file, simply send the file to the client.
      $helpers = \OpenBroadcaster\Support\Helpers::get_instance();
      \OpenBroadcaster\Support\Helpers::sendfile($path);
    } else {
      http_response_code(404);
      exit();
    }
  }
  // No module file? Return 404
  else {
    http_response_code(404);
    exit();
  }
}

if (is_file(OB_LOCAL . '/VERSION')) {
    $version = trim(file_get_contents(OB_LOCAL . '/VERSION'));
} else {
    $version = false;
}

// are we logged in? if not, redirect to welcome page.
$user = \OpenBroadcaster\Support\User::get_instance();
if (!isset($_COOKIE['ob_auth_id']) || !isset($_COOKIE['ob_auth_key']) || !$user->auth($_COOKIE['ob_auth_id'], $_COOKIE['ob_auth_key'])) {
    header('Location: /welcome/');
    die();
}

// we're logged in! continue with load.
$models = \OpenBroadcaster\Support\Models::get_instance();
$js_files    = $models->ui('js_files');
$css_files   = $models->ui('css_files');
$image_files = $models->ui('image_files');


$js_dependencies = [
  'node_modules/jquery/dist/jquery.min.js',
  'node_modules/jquery-migrate/dist/jquery-migrate.min.js',
  'node_modules/video.js/dist/video.min.js',
  'node_modules/dayjs/dayjs.min.js',
  'node_modules/easymde/dist/easymde.min.js',
  'bundles/chrono-bundle.js'
];

?><!DOCTYPE html>
<html lang="en">
<head>
  <script>
      /*
        // List of events to monitor
        var eventsToMonitor = ['mousedown', 'mouseup', 'click', 'dragstart', 'drag', 'dragend'];

        // Function to handle logging
        function logEvent(event) {
            console.log('Event:', event.type, 'on element:', event.target);
        }

        // Attaching event listeners
        eventsToMonitor.forEach(function(eventType) {
            document.addEventListener(eventType, logEvent, true); // using capture phase
        });
      */
  </script>
  <meta charset="utf-8">
  <title>OpenBroadcaster</title>
  <script type="importmap">
      {
          "imports": {
          "immutable": "./node_modules/immutable/dist/immutable.es.js",
          "sass": "./node_modules/sass/sass.default.js"
          }
      }
  </script>
<?php
foreach ($js_dependencies as $file) {
    echo '<script type="text/javascript" src="' . $file . '?v=' . filemtime(__DIR__ . '/' . $file) . '"></script>' . PHP_EOL;
}

// get a recursive list of files in "ui" and add them as js modules
$jsModuleIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(OB_LOCAL . '/public/ui'));
foreach ($jsModuleIterator as $file) {
    $publicPath = substr($file->getPathname(), strrpos($file->getPathname(), '/public/') + 8);
    if ($file->getExtension() !== 'js') {
        continue;
    }
    echo '<script type="module" src="/' . $publicPath . '?v=' . filemtime($file->getPathname()) . '"></script>' . PHP_EOL;
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

  <link type="text/css" href="extras/opensans/opensans.css?v=<?=filemtime('extras/opensans/opensans.css')?>" rel="stylesheet">
  <link type="text/css" href="extras/jquery-ui-darkness/jquery-ui.min.css?v=<?=filemtime('extras/jquery-ui-darkness/jquery-ui.min.css')?>" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="extras/jquery-ui-timepicker-addon.css?v=<?=filemtime('extras/jquery-ui-timepicker-addon.css')?>">
  <link rel="stylesheet" type="text/css" href="node_modules/video.js/dist/video-js.min.css?v=<?=filemtime('node_modules/video.js/dist/video-js.min.css')?>">

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

  <?php foreach ($js_files as $file) {
    // need to go prev dir since we're in public/ for modules or filemtime will cause warnings
    $mtime = (strpos($file, '/modules/') === 0) ? filemtime(OB_LOCAL . $file) : filemtime(OB_LOCAL . '/public/' . $file);
  ?>
    <script type="text/javascript" src="<?=$file?>?v=<?=$mtime?>"></script>
  <?php } ?>

  <?php foreach ($css_files as $file) {
    // need to go prev dir since we're in public/ for modules or filemtime will cause warnings
    $mtime = (strpos($file, '/modules/') === 0) ? filemtime(OB_LOCAL . $file) : filemtime(OB_LOCAL . '/public/' . $file);
  ?>
    <link rel="stylesheet" type="text/css" href="<?=$file?>?v=<?=$mtime?>">
  <?php } ?>

  <?php if (!empty($user->userdata['dyslexia_friendly_font'])) { ?>
    <link rel="stylesheet" type="text/css" href="extras/opendyslexic/opendyslexic.css?v=<?=urlencode($version)?>">
  <?php } ?>

  <link rel="stylesheet" href="/node_modules/@fortawesome/fontawesome-free/css/all.min.css?v=<?=filemtime('node_modules/@fortawesome/fontawesome-free/css/all.min.css')?>">

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

<?php

?>