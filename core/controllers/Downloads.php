<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Endpoints where the output is raw/binary.
 *
 * @package Controller
 */
namespace OpenBroadcaster\Controllers;

use OpenBroadcaster\Base\Controller;
use OpenBroadcaster\Support\Helpers;
use OpenBroadcaster\Support\IO;

class Downloads extends Controller
{
    private $io;

    public function __construct()
    {
        parent::__construct();
        $this->io = IO::get_instance();
    }

    /**
     * Download media item version.
     *
     * @param id Media ID
     * @param version Media version
     *
     * @route GET /downloads/media/(:id:)/version/(:version:)
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
     * @route GET /downloads/media/(:id:)
     */
    public function media()
    {
        $id = $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        Helpers::download_media_auth($media);

        $fullpath = Helpers::media_file($media);

        $this->download($fullpath, $media['filename']);
    }

    /**
     * Get all restrictions.
     *
     * @param id Media ID
     *
     * @route GET /downloads/media/(:id:)/preview/
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

        Helpers::preview_media_auth($media);

        $cache_dir = OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1];

        // create dir if not exists
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0777, true);
        }

        // server error if dir not exists
        if (!file_exists($cache_dir)) {
            $this->error(OB_ERROR_SERVER);
        }

        $media_file = Helpers::media_file($media);

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

        Helpers::sendfile($cache_file);
    }

    /**
     * Get thumbnail data for media item.
     *
     * @param id Media ID
     *
     * @route GET /downloads/media/(:id:)/thumbnail/
     */
    public function thumbnail()
    {
        $id = $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        Helpers::preview_media_auth($media);

        // get thumbnail
        $file = $this->models->media('thumbnail_file', ['media' => $id]);

        if (!$file || !file_exists($file)) {
            $this->error(OB_ERROR_NOTFOUND);
        } else {
            // if version is specified, allow cache if version matches expected
            if (($_GET['v'] ?? null) && $_GET['v'] == filemtime($file) && $media['status'] == 'public') {
                header('Cache-Control: public, max-age=' . (60 * 60 * 24 * 7)); // cache for 7 days
            }
            Helpers::sendfile($file);
        }
    }

    /**
     * Get thumbnail data for playlist item.
     *
     * @param id Playlist ID
     *
     * @route GET /downloads/playlists/(:id:)/thumbnail/
     */
    public function playlistThumbnail()
    {
        $id = $this->data('id');
        $playlist = $this->models->playlists('get_by_id', $id);

        if (!$playlist) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        // get thumbnail
        $file = $this->models->playlists('thumbnail_file', ['playlist' => $id]);

        if (!$file || !file_exists($file)) {
            $this->error(OB_ERROR_NOTFOUND);
        } else {
            // if version is specified, allow cache if version matches expected
            if (($_GET['v'] ?? null) && $_GET['v'] == filemtime($file) && $playlist['status'] == 'public') {
                header('Cache-Control: public, max-age=' . (60 * 60 * 24 * 7)); // cache for 7 days
            }
            Helpers::sendfile($file);
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

        Helpers::sendfile($fullpath, null, true);

        // don't want any more output after outputting file
        die();
    }
}
