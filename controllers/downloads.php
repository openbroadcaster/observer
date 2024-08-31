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

        // check permissions
        if ($media['status'] != 'public') {
            $this->user->require_authenticated();
            $is_media_owner = $media['owner_id'] == $this->user->param('id');

            // private media requires manage_media if this is not the media owner
            if ($media['status'] == 'private' && !$is_media_owner) {
                $this->user->require_permission('manage_media');
            }
        }

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

            header('Content-Type: audio/mpeg');
        } else {
            // video preview transcode
            $cache_file = $cache_dir . '/' . $media['id'] . '.mp4';

            if (!file_exists($cache_file)) {
                $dest_width = 1280;
                $dest_height = 720;

                $strtr_array = ['{infile}' => $media_file, '{outfile}' => $cache_file, '{width}' => $dest_width, '{height}' => $dest_height];
                exec(strtr(OB_TRANSCODE_VIDEO_MP4, $strtr_array));
            }

            // if file not exists, server error
            if (!file_exists($cache_file)) {
                $this->error(OB_ERROR_SERVER);
            }

            header('Content-Type: video/mp4');
        }
        header('Content-Length: ' . filesize($cache_file));
        $fp = fopen($cache_file, 'rb');
        fpassthru($fp);
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

        // check permissions
        if ($media['status'] != 'public') {
            $this->user->require_authenticated();
            $is_media_owner = $media['owner_id'] == $this->user->param('id');
            if ($media['status'] == 'private' && !$is_media_owner) {
                $this->user->require_permission('manage_media');
            }
        }

        // get thumbnail
        $file = $this->models->media('thumbnail_file', ['media' => $id]);

        if (!$file || !file_exists($file)) {
            $this->error(OB_ERROR_NOTFOUND);
        } else {
            $mime = mime_content_type($file);
            $contents = file_get_contents($file);
            header('Content-Type: ' . $mime);
            echo $contents;
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

        header("Access-Control-Allow-Origin: *");
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($fullpath));
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        readfile($fullpath);

        // don't want any more output after outputting file
        die();
    }
}
