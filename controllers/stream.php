<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Controller for managing stream and ondemand transcoding.
 *
 * @package Controller
 */
class Stream extends OBFController
{
    private $io;

    public function __construct()
    {
        parent::__construct();
        $this->io = OBFIO::get_instance();
    }

    /**
     * Get stream m3u8 file for media item.
     *
     * @param id Media ID
     *
     * @route GET /v2/stream/(:id:)
     */
    public function stream()
    {
        $id = (int) $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        if ($media['type'] != 'audio' && $media['type'] != 'video') {
            $this->error(OB_ERROR_NOTFOUND);
        }

        if ($media['type'] == 'audio') {
            $index_file = 'audio.m3u8';
        } else {
            $index_file = 'prog_index.m3u8';
        }

        OBFHelpers::preview_media_auth($media);

        $locationA = $media['file_location'][0];
        $locationB = $media['file_location'][1];
        $dir = OB_CACHE . "/streams/$locationA/$locationB/$id/";

        $file = $_GET['file'] ?? null;
        $ondemand = $_GET['ondemand'] ?? null;

        // ondemand only for audio
        if ($media['type'] != 'audio' && $ondemand) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        // update dir if ondemand specified
        if ($file && $ondemand) {
            // make sure ondemand only alphanumeric
            if (!ctype_alnum($ondemand)) {
                $this->error(OB_ERROR_NOTFOUND);
            }

            $dir = OB_CACHE . "/ondemand/$ondemand/";
            if (! is_dir($dir)) {
                $this->error(OB_ERROR_SERVER);
            }
        }

        if ($file) {
            // make sure filename is valid and safe
            if (preg_match('/^[a-zA-Z0-9]+\.ts$/', $file) !== 1) {
                 $this->error(OB_ERROR_NOTFOUND);
            }

            if ($ondemand && !file_exists($dir . $file)) {
                // find index by removing non-numeric characters from file
                $segment_index = preg_replace('/[^0-9]/', '', $file);
                $this->ondemand_transcode(OBFHelpers::media_file($media), $dir, $segment_index, 10);
            }

            // make sure it exists
            if (!file_exists($dir . $file)) {
                $this->error(OB_ERROR_NOTFOUND);
            }

            // add a "last access" file
            file_put_contents($dir . 'last_access_time', time());
            file_put_contents($dir . 'last_access_file', $file);

            // output
            OBFHelpers::sendfile($dir . $file, 'video/mp2t');
        } else {
            // no index file but we can do this on-demand
            if ($media['type'] == 'audio' && !file_exists($dir . $index_file)) {
                $this->output_modified_m3u8($this->ondemand_m3u8($media));
            }

            // no index file and no support for video on demand encoding yet
            if (!file_exists($dir . $index_file)) {
                echo 4;
                $this->error(OB_ERROR_NOTFOUND);
            }

            // we have an index file, just send that
            $this->output_modified_m3u8(file_get_contents($dir . $index_file));
        }
    }

    private function ondemand_m3u8($media)
    {
        $segment_duration = 10;

        $randid = bin2hex(random_bytes(20));
        $output_dir = OB_CACHE . '/ondemand/' . $randid . '/';
        mkdir($output_dir, 0775, true);

        $media_file = OBFHelpers::media_file($media);

        if (!file_exists($media_file)) {
            $this->error(OB_ERROR_NOTFOUND);
            die();
        }

        $total_duration = trim(shell_exec('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($media_file)));
        if (!$total_duration) {
            $this->error(OB_ERROR_SERVER);
            die();
        }

        $this->ondemand_transcode($media_file, $output_dir, 0, $segment_duration);

        $m3u8 = "#EXTM3U\n";
        $m3u8 .= "#EXT-X-VERSION:3\n";
        $m3u8 .= "#EXT-X-TARGETDURATION:$segment_duration\n";
        $m3u8 .= "#EXT-X-MEDIA-SEQUENCE:0\n";

        $current_position = 0;
        $current_index = 0;

        while ($current_position < $total_duration) {
            $file = "audio$current_index.ts";
            $m3u8 .= "#EXTINF:" . min($segment_duration, $total_duration - $current_position) . ",\n";
            $m3u8 .= "$file\n";
            $current_position += $segment_duration;
            $current_index++;
        }

        // used by output_modified_m3u8
        $_GET['ondemand'] = $randid;

        $m3u8 .= "#EXT-X-ENDLIST\n";

        return $m3u8;
    }

    private function ondemand_transcode($media_file, $output_dir, $segment_index, $segment_duration)
    {
        // check transcode.pid and stop it if it exists
        $pid_file = $output_dir . 'transcode.pid';
        if (file_exists($pid_file)) {
            $pid = file_get_contents($pid_file);
            exec("kill $pid");
            // remove all files in the directory (the new transcode will invalidate the previous ones due to index markers and such in the ts files)
            array_map('unlink', glob($output_dir . '*'));
        }

        $start_time = (int) $segment_index * (int) $segment_duration;

        // launch transcode into the background
        // this writes a transcode.pid file with the pid of the script, which is removed when the script is done (so we can easily check if done)
        $pid = exec(<<<CMD
        nohup bash -c '
            echo \$\$ > {$output_dir}transcode.pid;
            ffmpeg -i "{$media_file}" \
            -map 0:a -hls_list_size 0 -hls_time {$segment_duration} \
            -start_number {$segment_index} -ss {$start_time} -strict -2 "{$output_dir}audio.m3u8" -hide_banner;
            rm {$output_dir}transcode.pid
        ' > /dev/null 2>&1 & echo \$!
        CMD
        );

        // wait for the audio0 file to be created
        $first_ts_file = $output_dir . "audio$segment_index.ts";
        $timeout = strtotime('+3 seconds');

        while (!file_exists($first_ts_file) && time() < $timeout) {
            usleep(100000);
        }

        if (!file_exists($first_ts_file)) {
            $this->error(OB_ERROR_SERVER);
            die();
        }
    }

    private function output_modified_m3u8($data)
    {
        // get all lines starting with #EXTINF, then modify the following line that doesn't start with #
        $lines = explode("\n", $data);

        foreach ($lines as &$line) {
            if (!str_ends_with($line, '.ts') && !str_ends_with($line, '.m3u8')) {
                continue;
            }

            if (str_starts_with($line, '#')) {
                continue;
            }

            $line = '?file=' . urlencode($line);

            if ($_GET['ondemand'] ?? null) {
                $line .= '&ondemand=' . urlencode($_GET['ondemand']);
            }

            if ($_GET['nonce'] ?? null) {
                $line .= '&nonce=' . urlencode($_GET['nonce']);
            }
        }

        $data = implode("\n", $lines);

        header('Content-Type: application/x-mpegURL');
        echo $data;

        die();
    }

    private function error($code)
    {
        $this->io->error($code);
        die();
    }
}
