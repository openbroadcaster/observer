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
