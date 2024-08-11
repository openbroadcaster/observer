<?php

namespace OB\Classes\Cron;

use OB\Classes\Base\Cron;

class ExtractThumbnails extends Cron
{
    public function interval(): int
    {
        return 60;
    }

    public function run(): bool
    {
        $db = \OBFDB::get_instance();

        // get all media that needs thumbnail to extract
        $db->query('SELECT * FROM media WHERE 
            (thumbnail_version IS NULL OR thumbnail_version < 2) AND
            type != "image"');

        $media = $db->assoc_list();

        foreach ($media as $item) {
            $input_file = OB_MEDIA . '/' . $item['file_location'][0] . '/' . $item['file_location'][1] . '/' . $item['filename'];
            $output_dir = OB_THUMBNAILS . '/media/' . $item['file_location'][0] . '/' . $item['file_location'][1];
            $output_file = $output_dir . '/' . $item['id'] . '.webp';

            // create output dir if needed
            if (!is_dir($output_dir)) {
                mkdir($output_dir, 0777, true);
            }

            // if thumbnail exists, don't regenerate just update version
            $thumbnail_search = glob(OB_THUMBNAILS . '/media/' . $item['file_location'][0] . '/' . $item['file_location'][1] . '/' . $item['id'] . '.*');
            foreach ($thumbnail_search as $thumbnail_file) {
                $db->query('UPDATE media SET thumbnail_version = 2 WHERE id = ' . $db->escape($item['id']));
                continue;
            }

            // no thumbnail in thumbnail dir, generate it
            switch ($item['type']) {
                case 'video':
                    $success = $this->runVideo($item, $input_file, $output_file);
                    break;
                case 'audio':
                    $success = $this->runAudio($item, $input_file, $output_file);
                    break;
                case 'document':
                    $success = $this->runDocument($item, $input_file, $output_file);
                    break;
            }

            if ($success) {
                $db->query('UPDATE media SET thumbnail_version = 2 WHERE id = ' . $db->escape($item['id']));
            }
        }

        return false;
    }

    private function runVideo($item, $input_file, $output_file): bool
    {
        echo 'generating video thumbnail for ' . $item['id'] . PHP_EOL;

        $success = false;
        $duration = $item['duration'];

        if ($duration) {
            // for short videos, start at the beginning.
            // for long videos, start at 25%
            if ($duration < 60) {
                $start = 0.00;
            } else {
                $start = $duration / 4;
            }

            $start_hours = floor($start / 3600);
            $start -= $start_hours * 3600;
            $start_minutes = floor($start / 60);
            $start -= $start_minutes * 60;
            $start_seconds = $start;

            if ($start_hours < 10) {
                $start_hours = '0' . $start_hours;
            }
            if ($start_minutes < 10) {
                $start_minutes = '0' . $start_minutes;
            }
            if ($start_seconds < 10) {
                $start_seconds = '0' . $start_seconds;
            }

            $start = $start_hours . ':' . $start_minutes . ':' . round($start_seconds, 2);

            // get unique dir name
            $tmp_dir = tempnam(sys_get_temp_dir(), 'ob_');

            // tempnam creates a file, unlink and make it a directory
            unlink($tmp_dir);
            mkdir($tmp_dir);

            // get 5 keyframes starting at 25% into the video.
            $command = 'ffmpeg -ss ' . escapeshellarg($start) . ' -i ' . escapeshellarg($input_file) . ' -vf "select=eq(pict_type\,I), scale=w=600:h=600:force_original_aspect_ratio=decrease" -vsync vfr -vframes 5 -q:v 0 -compression_level 6 -lossless 1 ' . escapeshellarg($tmp_dir . '/thumb%04d.webp') . ' -hide_banner 2>&1';
            $return_var = 0;
            exec($command, $output, $return_var);

            // pick thumbnail with largest filesize
            $thumbs = glob($tmp_dir . '/thumb*');

            $thumb_size = 0;
            $thumb_selected = false;

            foreach ($thumbs as $thumb) {
                if (filesize($thumb) > $thumb_size) {
                    $thumb_selected = $thumb;
                    $thumb_size = filesize($thumb_selected);
                }
            }

            if ($thumb_selected) {
                copy($thumb_selected, $output_file);
                $success = true;
            }

            // clean up
            foreach ($thumbs as $thumb) {
                unlink($thumb);
            }
            rmdir($tmp_dir);
        }

        return $success;
    }

    private function runAudio($item, $input_file, $output_file): bool
    {
        echo 'generating audio thumbnail for ' . $item['id'] . PHP_EOL;

        $command = 'ffmpeg -y -i ' . escapeshellarg($input_file) . ' -vf "scale=w=600:h=600:force_original_aspect_ratio=decrease" -q:v 0 -compression_level 6 -lossless 1 ' . escapeshellarg($output_file) . ' -hide_banner 2>&1';
        $return_var = 0;
        exec($command, $output, $return_var);
        $success = true; // assume success, because will fail if no album art, but that's okay.

        return $success;
    }

    private function runDocument($item, $input_file, $output_file): bool
    {
        echo 'generating document thumbnail for ' . $item['id'] . PHP_EOL;

        $success = false;

        // Use a high resolution for good quality
        $resolution = 300;

        // Create a temporary file with .tiff extension
        $tempFile = tempnam(sys_get_temp_dir(), 'gs_output_');
        $tempFileTiff = $tempFile . '.tiff';
        rename($tempFile, $tempFileTiff);

        // Ghostscript command to convert PDF to TIFF
        $gsCommand = sprintf(
            'gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=tiff24nc -dFirstPage=1 -dLastPage=1 ' .
            '-r%d -dTextAlphaBits=4 -dGraphicsAlphaBits=4 ' .
            '-sOutputFile=%s %s 2>&1',
            $resolution,
            escapeshellarg($tempFileTiff),
            escapeshellarg($input_file)
        );

        // Execute Ghostscript command
        exec($gsCommand, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($tempFileTiff)) {
            error_log("Ghostscript conversion failed: " . implode("\n", $output));
            if (file_exists($tempFileTiff)) {
                unlink($tempFileTiff);
            }
            return false;
        }

        try {
            // Now use ImageMagick to resize the image and convert to JPG
            $im = new \Imagick();
            $im->readImage($tempFileTiff);
            $im->setImageBackgroundColor('white'); // Set white background
            $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE); // Remove alpha channel
            $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN); // Flatten image
            $im->thumbnailImage(1200, 1200, true);
            $im->setImageFormat('webp');
            $im->setOption('webp:lossless', 'true');
            $im->writeImage($output_file);
            $im->clear();
            $im->destroy();

            $success = true;
        } catch (\ImagickException $e) {
            error_log("ImageMagick error: " . $e->getMessage());
        } finally {
            if (file_exists($tempFileTiff)) {
                unlink($tempFileTiff);
            }
        }

        return $success;
    }
}
