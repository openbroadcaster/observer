<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// set default cache control header (if not overridden by controller code later)
if (php_sapi_name() !== 'cli') {
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

// set some constants
if (!defined('OB_ERROR_BAD_POSTDATA')) {
    define('OB_ERROR_BAD_POSTDATA', 1);
}
if (!defined('OB_ERROR_BAD_CONTROLLER')) {
    define('OB_ERROR_BAD_CONTROLLER', 2);
}
if (!defined('OB_ERROR_BAD_DATA')) {
    define('OB_ERROR_BAD_DATA', 3);
}
if (!defined('OB_ERROR_DENIED')) {
    define('OB_ERROR_DENIED', 4);
}
if (!defined('OB_ERROR_NOTFOUND')) {
    define('OB_ERROR_NOTFOUND', 5);
}
if (!defined('OB_ERROR_SERVER')) {
    define('OB_ERROR_SERVER', 6);
}
if (!defined('OB_LOCAL')) {
    define('OB_LOCAL', realpath(__DIR__ . '/../'));
}

// load config
if (!file_exists(OB_LOCAL . '/config.php')) {
    die('Settings file (config.php) not found.');
}
require_once(OB_LOCAL . '/config.php');

// set appropriate SENDFILE header based on server
if (!defined('OB_SENDFILE_HEADER')) {
    define('OB_SENDFILE_HEADER', false);
}

// set defaults if not set
if (!defined('OB_UPLOADS')) {
    define('OB_UPLOADS', OB_LOCAL . '/uploads');
}
if (!defined('OB_MEDIA_FILESIZE_LIMIT')) {
    define('OB_MEDIA_FILESIZE_LIMIT', 1024);
}
if (!defined('OB_INIT_VERIFY')) {
    define('OB_INIT_VERIFY', false);
}

// set default OB_MEDIA_VERIFY to true
if (!defined('OB_MEDIA_VERIFY')) {
    define('OB_MEDIA_VERIFY', true);
}
if (is_string(OB_MEDIA_VERIFY)) {
    // OB_MEDIA_VERIFY is already set to a command
    define('OB_MEDIA_VERIFY_CMD', OB_MEDIA_VERIFY);
} elseif (OB_MEDIA_VERIFY) {
    // OB_MEDIA_VERIFY is set to default command
    define('OB_MEDIA_VERIFY_CMD', 'ffmpeg -i {infile} -f null -');
} else {
    // OB_MEDIA_VERIFY is false
    define('OB_MEDIA_VERIFY_CMD', false);
}

// set default transcode commands
if (!defined('OB_TRANSCODE_AUDIO_MP3')) {
    define('OB_TRANSCODE_AUDIO_MP3', 'ffmpeg -i {infile} -q 9 -ac 1 -ar 22050 {outfile}');
}
if (!defined('OB_TRANSCODE_AUDIO_OGG')) {
    define('OB_TRANSCODE_AUDIO_OGG', 'ffmpeg -i {infile} -acodec libvorbis -q 0 -ac 1 -ar 22050 {outfile}');
}
if (!defined('OB_TRANSCODE_VIDEO_MP4')) {
    define('OB_TRANSCODE_VIDEO_MP4', 'ffmpeg -i {infile} -crf 40 -vcodec libx264 -s {width}x{height} -ac 1 -ar 22050 {outfile}');
}
if (!defined('OB_TRANSCODE_VIDEO_OGV')) {
    define('OB_TRANSCODE_VIDEO_OGV', 'ffmpeg -i {infile} -q 0 -s {width}x{height} -acodec libvorbis -ac 1 -ar 22050 {outfile}');
}

// most things are done in UTC.  sometimes the tz is set to the player's tz for a 'strtotime' +1month,etc. type calculation which considers DST.
date_default_timezone_set('Etc/UTC');

// Use autoloading to include controller and model files.
spl_autoload_register(function ($className) {
    $namespaceMap = [
        'OpenBroadcaster\\Base\\' => OB_LOCAL . '/core/base/',
        'OpenBroadcaster\\Models\\' => OB_LOCAL . '/core/models/',
        'OpenBroadcaster\\Controllers\\' => OB_LOCAL . '/core/controllers/',
        'OpenBroadcaster\\Metadata\\' => OB_LOCAL . '/core/metadata/',
        'OpenBroadcaster\\Cron\\' => OB_LOCAL . '/core/cron/',
        'OpenBroadcaster\\Remote\\' => OB_LOCAL . '/core/remote/',
    ];

    $modulesDir = OB_LOCAL . '/modules/';
    foreach (scandir($modulesDir) as $module) {
        if ($module !== '.' && $module !== '..' && is_dir($modulesDir . $module)) {
            $moduleNamespace = str_replace(' ', '', ucwords(str_replace('_', ' ', $module)));
            $namespaceMap["OpenBroadcaster\\Modules\\{$moduleNamespace}\\Cron\\"] = $modulesDir . $module . '/cron/';
            // TODO: Additional module namespacing to add to map.
        }
    }

    foreach ($namespaceMap as $namespacePrefix => $baseDir) {
        if (strpos($className, $namespacePrefix) === 0) {
            $relativeClass = substr($className, strlen($namespacePrefix));

            // Convert further namespace separators to directory separators.
            $file = $baseDir . str_replace('\\', '/', $relativeClass);

            // Check if model, which has a weird naming scheme (possible TODO, currently breaks
            // too many things).
            if (str_ends_with($file, 'Model')) {
                $file = strtolower(substr($file, 0, -5)) . '_model';
            }

            // Add extension.
            $file = $file . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Require core files (TODO: add to autoloading, will need to be namespaced first).
$require_from = [
    OB_LOCAL . '/core/core',
];
foreach ($require_from as $dir) {
    $classes_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($classes_iterator as $file) {
        if ($file->isFile() && $file->getExtension() == 'php') {
            require_once($file->getPathname());
        }
    }
}

// load third party components
require_once(OB_LOCAL . '/vendor/autoload.php');
//require('extras/PHPMailer/src/Exception.php');
//require('extras/PHPMailer/src/PHPMailer.php');

// verify proper functioning if requested in config
$init_verify_running = false;
if (!$init_verify_running && OB_INIT_VERIFY && is_array(OB_INIT_VERIFY) && !defined('OB_CLI')) {
    $init_verify_running = true;
    require_once(OB_LOCAL . '/public/updates/checker.php');
    $checker = new \OBFChecker();
    $methods = get_class_methods($checker);

    foreach (OB_INIT_VERIFY as $check) {
        if (is_string($check) && in_array($check, $methods)) {
            $result = $checker->$check();
            if ($result[2] > 0) {
                http_response_code(503);
                die('OpenBroadcaster temporarily unavailable.');
            }
        }
    }
}
