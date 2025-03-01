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

/**
 * Endpoints where the output is raw/binary.
 *
 * @package Controller
 */
class Downloads extends OBFController
{
    private $io;

    public function __construct()
    {
        parent::__construct();
        $this->io = OBFIO::get_instance();
    }

    // send direct with server (if possible) to avoid keeping PHP proecss open, memory limit issues, etc.
    private function sendfile($file, $type = null, $download = false)
    {
        if ($download) {
            $type = 'application/octet-stream';
            header("Access-Control-Allow-Origin: *");
            header('Content-Description: File Transfer');
            header("Content-Transfer-Encoding: binary");
        }

        if (!$type) {
            $type = mime_content_type($file);
        }

        header('Content-Type: ' . $type);
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));

        if (OB_SENDFILE_HEADER) {
            header(OB_SENDFILE_HEADER . ': ' . $file);
            die();
        } else {
            readfile($file);
        }

        die();
    }

    private function media_file($media)
    {
        if ($media['is_archived'] == 1) {
            $filedir = OB_MEDIA_ARCHIVE;
        } elseif ($media['is_approved'] == 0) {
            $filedir = OB_MEDIA_UPLOADS;
        } else {
            $filedir = OB_MEDIA;
        }

        return $filedir . '/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/' . $media['filename'];
    }

    /**
     * Download media item version.
     *
     * @param id Media ID
     * @param version Media version
     *
     * @route GET /v2/downloads/media/(:id:)/version/(:version:)
     */
    public function version()
    {
        $id = $this->data('id');
        $version = $this->data('version');

        // always need authorized user to download a version
        $this->user->require_authenticated();

        // always need manage_media_versions to download a version
        $this->user->require_permission('manage_media_versions');

        // get media
        $media = $this->models->media('get_by_id', ['id' => $id]);
        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        // if not media owner, also need manage_media permission
        $is_media_owner = $media['owner_id'] == $this->user->param('id');
        if (!$is_media_owner) {
            $this->user->require_permission('manage_media');
        }

        // get version
        $this->db->where('media_id', $id);
        $this->db->where('created', $version);
        $version = $this->db->get_one('media_versions');
        if (!$version) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        $fullpath = (defined('OB_MEDIA_VERSIONS') ? OB_MEDIA_VERSIONS : OB_MEDIA . '/versions') .
                        '/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/' .
                        $version['media_id'] . '-' . $version['created'] . '.' . $version['format'];
        $filename = $version['media_id'] . '-' . $version['created'] . '.' . $version['format'];

        $this->download($fullpath, $filename);
    }

    /**
     * Download media item.
     *
     * @param id Media ID
     *
     * @route GET /v2/downloads/media/(:id:)
     */
    public function media()
    {
        $id = $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        $this->download_media_auth($media);

        $fullpath = $this->media_file($media);

        $this->download($fullpath, $media['filename']);
    }

    /**
     * Get all restrictions.
     *
     * @param id Media ID
     *
     * @route GET /v2/downloads/media/(:id:)/preview/
     */
    public function preview()
    {
        $id = $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        // not found if type is not audio or video
        if ($media['type'] != 'audio' && $media['type'] != 'video') {
            $this->error(OB_ERROR_NOTFOUND);
        }

        $this->preview_media_auth($media);

        $cache_dir = OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1];

        // create dir if not exists
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0777, true);
        }

        // server error if dir not exists
        if (!file_exists($cache_dir)) {
            $this->error(OB_ERROR_SERVER);
        }

        $media_file = $this->media_file($media);

        if ($media['type'] == 'audio') {
            // audio preview transcode
            $cache_file = $cache_dir . '/' . $media['id'] . '_audio.mp3';

            if (!file_exists($cache_file)) {
                $strtr_array = ['{infile}' => $media_file, '{outfile}' => $cache_file];
                exec(strtr(OB_TRANSCODE_AUDIO_MP3, $strtr_array));
            }

            // if file not exists, server error
            if (!file_exists($cache_file)) {
                $this->error(OB_ERROR_SERVER);
            }
        } else {
            // video preview transcode
            $cache_file = $cache_dir . '/' . $media['id'] . '.mp4';

            if (!file_exists($cache_file)) {
                $dest_width = 640;
                $dest_height = 480;

                $strtr_array = ['{infile}' => $media_file, '{outfile}' => $cache_file, '{width}' => $dest_width, '{height}' => $dest_height];
                exec(strtr(OB_TRANSCODE_VIDEO_MP4, $strtr_array));
            }

            // if file not exists, server error
            if (!file_exists($cache_file)) {
                $this->error(OB_ERROR_SERVER);
            }
        }

        $this->sendfile($cache_file);
    }

    /**
     * Get all restrictions.
     *
     * @param id Media ID
     *
     * @route GET /v2/downloads/media/(:id:)/thumbnail/
     */
    public function thumbnail()
    {
        $id = $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        $this->preview_media_auth($media);

        // get thumbnail
        $file = $this->models->media('thumbnail_file', ['media' => $id]);

        if (!$file || !file_exists($file)) {
            $this->error(OB_ERROR_NOTFOUND);
        } else {
            $this->sendfile($file);
        }
    }

    /**
     * Get stream m3u8 file for media item.
     *
     * @param id Media ID
     *
     * @route GET /v2/downloads/media/(:id:)/stream/
     */
    public function stream()
    {
        $id = (int) $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        $this->preview_media_auth($media);

        $locationA = $media['file_location'][0];
        $locationB = $media['file_location'][1];

        $dir = OB_CACHE . "/streams/$locationA/$locationB/$id/";

        if ($media['type'] == 'audio') {
            $m3u8 = $dir . 'audio.m3u8';
        } elseif ($media['type'] == 'video') {
            $m3u8 = $dir . 'prog_index.m3u8';
        } else {
            $this->error(OB_ERROR_NOTFOUND);
        }

        if (!file_exists($m3u8)) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        if ($_GET['file'] ?? null) {
            $file = $_GET['file'];
            $realpath = realpath($dir . $file);
            $pathinfo = pathinfo($realpath);

            if (!$realpath || strpos($realpath, $dir) !== 0) {
                $this->error(OB_ERROR_NOTFOUND);
            }

            // make sure extension is m3u8 or ts
            if ($pathinfo['extension'] != 'm3u8' && $pathinfo['extension'] != 'ts') {
                $this->error(OB_ERROR_NOTFOUND);
            }

            if ($pathinfo['extension'] == 'ts') {
                $this->sendfile($realpath, 'video/mp2t');
            } else {
                $this->output_modified_m3u8($realpath);
            }
        }

        $this->output_modified_m3u8($m3u8);
    }

    private function output_modified_m3u8($m3u8)
    {
        // get m3u8 data
        $data = file_get_contents($m3u8);

        // get all lines starting with #EXTINF, then modify the following line that doesn't start with #
        $lines = explode("\n", $data);

        foreach ($lines as &$line) {
            if (!str_ends_with($line, '.ts') && !str_ends_with($line, '.m3u8')) {
                continue;
            }

            if (str_starts_with($line, '#')) {
                continue;
            }

            $line = '?file=' . $line;
        }

        $data = implode("\n", $lines);

        header('Content-Type: application/x-mpegURL');
        echo $data;

        die();
    }

    private function preview_media_auth($media)
    {
        // check permissions
        if ($media['status'] != 'public') {
            $this->user->require_authenticated();
            $is_media_owner = $media['owner_id'] == $this->user->param('id');
            if ($media['status'] == 'private' && !$is_media_owner) {
                $this->user->require_permission('manage_media');
            }
        }
    }

    private function download_media_auth($media)
    {
        // check permissions
        if ($media['status'] != 'public') {
            $this->user->require_authenticated();
            $is_media_owner = $media['owner_id'] == $this->user->param('id');

            // download requires download_media if this is not the media owner
            if (!$is_media_owner) {
                $this->user->require_permission('download_media');
            }

            // private media requires manage_media if this is not the media owner
            if ($media['status'] == 'private' && !$is_media_owner) {
                $this->user->require_permission('manage_media');
            }
        }
    }


    private function error($code)
    {
        $this->io->error($code);
        die();
    }

    private function download($fullpath, $filename)
    {
        if (!file_exists($fullpath)) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        $this->sendfile($fullpath, null, true);

        // don't want any more output after outputting file
        die();
    }
}
