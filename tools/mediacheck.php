<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/* run from command line, output list of missing media files. */

if (php_sapi_name()!='cli') {
    die('cli only');
}

require('../components.php');

$db = OBFDB::get_instance();

$db->query('select * from media order by id');

foreach ($db->assoc_list() as $nfo) {
    if ($nfo['is_archived'] == 1) {
        $dir = OB_MEDIA_ARCHIVE;
    } elseif ($nfo['is_approved'] == 0) {
        $dir = OB_MEDIA_UPLOADS;
    } else {
        $dir = OB_MEDIA;
    }

    $filename = $dir.'/'.$nfo['file_location'][0].'/'.$nfo['file_location'][1].'/'.$nfo['filename'];

    if (!file_exists($filename)) {
        echo $filename.PHP_EOL;

        // see if we can find the actual filename in that directory
        $check_files = scandir($dir.'/'.$nfo['file_location'][0].'/'.$nfo['file_location'][1]);
        $fix_filename = null;
        foreach ($check_files as $check_file) {
            if (preg_match('/'.$nfo['id'].'-/', $check_file)) {
                $fix_filename = $check_file;
                break;
            }
        }
        if ($fix_filename) {
            echo $nfo['filename'].' -> '.$fix_filename.PHP_EOL;
            // $db->where('id',$nfo['id']);
      // $db->update('media',['filename'=>$fix_filename]);
        }

        echo PHP_EOL;
    }
}
