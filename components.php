<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

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
    define('OB_LOCAL', __DIR__);
}

// use same working directory regardless of where our script is.
chdir(OB_LOCAL);

// load config
if (!file_exists('config.php')) {
    die('Settings file (config.php) not found.');
}
require_once('config.php');

// set defaults if not set
if (!defined('OB_ASSETS')) {
    define('OB_ASSETS', OB_LOCAL . '/assets');
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

// require class files
$require_from = [
    'classes/core',
    'classes/base',
    'classes/metadata'
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
require_once('vendor/autoload.php');
//require('extras/PHPMailer/src/Exception.php');
//require('extras/PHPMailer/src/PHPMailer.php');

// verify proper functioning if requested in config
$init_verify_running = false;
if (!$init_verify_running && OB_INIT_VERIFY && is_array(OB_INIT_VERIFY) && !defined('OB_CLI')) {
    $init_verify_running = true;
    require_once('updates/checker.php');
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
