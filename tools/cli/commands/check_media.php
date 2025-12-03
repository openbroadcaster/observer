<?php

namespace ob\tools\cli;

if (! defined('OB_CLI')) {
    die('Command line access only.');
}

require(__DIR__ . '/../../../components.php');

$db = \OBFDB::get_instance();

$db->query('select * from media order by id');

foreach ($db->assoc_list() as $nfo) {
    if ($nfo['is_archived'] == 1) {
        $dir = OB_MEDIA_ARCHIVE;
    } elseif ($nfo['is_approved'] == 0) {
        $dir = OB_MEDIA_UPLOADS;
    } else {
        $dir = OB_MEDIA;
    }

    $filename = $dir . '/' . $nfo['file_location'][0] . '/' . $nfo['file_location'][1] . '/' . $nfo['filename'];

    if (! file_exists($filename)) {
        echo 'Missing file: ' . $filename . PHP_EOL;

        // Try to find the actual filename in that directory.
        $check_files = scandir($dir . '/' . $nfo['file_location'][0] . '/' . $nfo['file_location'][1]);
        $fix_filename = null;

        foreach ($check_files as $check_file) {
            if (preg_match('/' . $nfo['id'] . '-/', $check_file)) {
                $fix_filename = $check_file;
                break;
            }
        }

        if ($fix_filename) {
            echo 'Probable file: ' . $nfo['filename'].' -> ' . $fix_filename . PHP_EOL;

            // NOTE: Filename in database does not get changed automatically at this point.
            // $db->where('id',$nfo['id']);
            // $db->update('media',['filename'=>$fix_filename]);
        }

        echo PHP_EOL;
    }
}

echo 'Successfully checked all media.' . PHP_EOL;
