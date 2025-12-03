<?php

namespace ob\tools\cli;

if (! defined('OB_CLI')) {
    die('Command line access only.');
}

require(__DIR__ . '/../../../components.php');

$db = \OBFDB::get_instance();

$db->query('select * from media order by id');

$media = $db->assoc_list();
$media_total = count($media);
$media_current = 0;
$media_errors = 0;

echo "Processing {$media_total} media items in database:" . PHP_EOL;

foreach ($media as $nfo) {
    // Check that media file exists in the correct location.
    if ($nfo['is_archived'] == 1) {
        $dir = OB_MEDIA_ARCHIVE;
    } elseif ($nfo['is_approved'] == 0) {
        $dir = OB_MEDIA_UPLOADS;
    } else {
        $dir = OB_MEDIA;
    }

    $filename = $dir . '/' . $nfo['file_location'][0] . '/' . $nfo['file_location'][1] . '/' . $nfo['filename'];

    if (! file_exists($filename)) {
        echo "\033[31mMissing file:\033[0m {$filename}" . PHP_EOL;

        $media_errors += 1;

        // Try to find the actual filename in that directory.
        $check_files = scandir($dir . '/' . $nfo['file_location'][0] . '/' . $nfo['file_location'][1]);
        $fix_filename = null;

        // Check for possibly renamed file.
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

    // Show media processing update.
    $media_current += 1;

    if (($media_current % 100) === 0) {
        echo "Processed {$media_current} media files." . PHP_EOL;
    }
}

echo
    "\033[32m" . str_pad($media_total - $media_errors, 2, ' ', STR_PAD_LEFT) . " pass\033[0m    " .
    "\033[31m" . str_pad($media_errors, 2, ' ', STR_PAD_LEFT) . " errors\033[0m    " . PHP_EOL;
