<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

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

require('components.php');

// set default transcoding commands
if (!defined('OB_TRANSCODE_AUDIO_MP3')) {
    define('OB_TRANSCODE_AUDIO_MP3', 'avconv -i {infile} -q 9 -ac 1 -ar 22050 {outfile}');
}
if (!defined('OB_TRANSCODE_AUDIO_OGG')) {
    define('OB_TRANSCODE_AUDIO_OGG', 'avconv -i {infile} -acodec libvorbis -q 0 -ac 1 -ar 22050 {outfile}');
}
if (!defined('OB_TRANSCODE_VIDEO_MP4')) {
    define('OB_TRANSCODE_VIDEO_MP4', 'avconv -i {infile} -crf 40 -vcodec libx264 -s {width}x{height} -ac 1 -ar 22050 {outfile}');
}
if (!defined('OB_TRANSCODE_VIDEO_OGV')) {
    define('OB_TRANSCODE_VIDEO_OGV', 'avconv -i {infile} -q 0 -s {width}x{height} -acodec libvorbis -ac 1 -ar 22050 {outfile}');
}

// Sanity check on ID
if (!empty($_GET['id']) && preg_match('/^[0-9]+$/', $_GET['id'])) {
    $media_id = $_GET['id'];
} else {
    die();
}

// COMPLETE AUTHENTICATION, usually handled by api.php

$user = OBFUser::get_instance();

$auth_id = null;
$auth_key = null;

// try to get an ID/key pair for user authorization.
if (!empty($_POST['i']) && !empty($_POST['k'])) {
    $auth_id = $_POST['i'];
    $auth_key = $_POST['k'];
} elseif (!empty($_COOKIE['ob_auth_id']) && !empty($_COOKIE['ob_auth_key'])) {
    // if not in post, try fetching from cookie.
    $auth_id = $_COOKIE['ob_auth_id'];
    $auth_key = $_COOKIE['ob_auth_key'];
}

// authorize our user (from post data, cookie data, whatever.)
$user->auth($auth_id, $auth_key);

class MediaPreview extends OBFController
{
    public function output($id, $download, $version = false)
    {
        global $user;

        $media_model = $this->load->model('Media');
        $media_model('get_init');
        $this->db->where('media.id', $id);
        $media = $this->db->get_one('media');

        if (!$media) {
            die();
        }

        if ($version !== false) {
            $this->db->where('media_id', $id);
            $this->db->where('created', $version);
            $version = $this->db->get_one('media_versions');
            if (!$version) {
                die();
            }
        }

        $type = strtolower($media['type']);
        $format = strtolower($media['format']);

        if (!preg_match('/^[a-z0-9_-]+\.[a-z0-9]+$/i', $media['filename'])) {
            die();
        }
        if (!preg_match('/^[A-Z0-9]{2}$/', $media['file_location'])) {
            die();
        }

        // check permissions
        $is_media_owner = $media['owner_id'] == $user->param('id');

        // preview/download both require manage_media if private media and not owner
        if ($media['status'] == 'private' && !$is_media_owner) {
            $user->require_permission('manage_media');
        }

        // download requires download_media if this is not the media owner
        if ($download && !$is_media_owner && !$version) {
            $user->require_permission('download_media');
        }

        // any version download requires manage_media_versions
        if ($version) {
            $user->require_permission('manage_media_versions');
        }

        // version download if not owner requires manage_media
        if ($version && !$is_media_owner) {
            $user->require_permission('manage_media');
        }

        // set media location for preview/download
        if (!$version) {
            if ($media['is_archived'] == 1) {
                $media_location = OB_MEDIA_ARCHIVE;
            } elseif ($media['is_approved'] == 0) {
                $media_location = OB_MEDIA_UPLOADS;
            } else {
                $media_location = OB_MEDIA;
            }

            $media_location .= '/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/';
            $media_file = $media_location . $media['filename'];

            $download_filename = $media['filename'];
        } else {
            $media_file = (defined('OB_MEDIA_VERSIONS') ? OB_MEDIA_VERSIONS : OB_MEDIA . '/versions') .
                            '/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/' .
                            $version['media_id'] . '-' . $version['created'] . '.' . $version['format'];

            $download_filename = $version['media_id'] . '-' . $version['created'] . '.' . $version['format'];
        }

        // get our cached data directory ready if we don't already have it.
        if (!file_exists(OB_CACHE . '/media')) {
            mkdir(OB_CACHE . '/media');
        }
        if (!file_exists(OB_CACHE . '/media/' . $media['file_location'][0])) {
            mkdir(OB_CACHE . '/media/' . $media['file_location'][0]);
        }
        if (!file_exists(OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1])) {
            mkdir(OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1]);
        }
        $cache_dir = OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1];

        if ($type == 'audio') {
      // download mode
            if ($download) {
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=' . $download_filename);
                header('Content-Type: application/octet-stream');
                header('Content-Length: ' . filesize($media_file));

                $fp = fopen($media_file, 'rb');
                fpassthru($fp);
                exit();
            }

            if (!empty($_GET['format']) && $_GET['format'] == 'mp3') {
                $audio_format = 'mp3';
            } else {
                $audio_format = 'ogg';
            }

            $cache_file = $cache_dir . '/' . $media['id'] . '_audio.' . $audio_format;

            if (!file_exists($cache_file)) {
                $strtr_array = array('{infile}' => $media_file, '{outfile}' => $cache_file);

                if ($audio_format == 'mp3') {
                    exec(strtr(OB_TRANSCODE_AUDIO_MP3, $strtr_array));
                } else {
                    exec(strtr(OB_TRANSCODE_AUDIO_OGG, $strtr_array));
                }
            }

            // temporary
            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

            if ($audio_format == 'mp3') {
                header('Content-Type: audio/mpeg');
            } else {
                header("Content-Type: audio/ogg");
            }
            header('Content-Length: ' . filesize($cache_file));
            $fp = fopen($cache_file, 'rb');
            fpassthru($fp);
        } elseif ($type == 'video') {
      // download mode
            if ($download) {
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=' . $download_filename);
                header('Content-Type: application/octet-stream');
                header('Content-Length: ' . filesize($media_file));

                $fp = fopen($media_file, 'rb');
                fpassthru($fp);
                exit();
            }

            if (!empty($_GET['w']) && !empty($_GET['h']) && preg_match('/^[0-9]+$/', $_GET['w']) && preg_match('/^[0-9]+$/', $_GET['h'])) {
                $dest_width = $_GET['w'];
                $dest_height = $_GET['h'];
            } else {
                $dest_width = 320;
                $dest_height = 240;
            }

            // we could use half resolution, then zoom by 200%. (faster encoding, lower birate) ...
            // $dest_width = round($dest_width/2);
            // $dest_height = round($dest_height/2);

            if (!empty($_GET['format']) && $_GET['format'] == 'mp4') {
                $video_format = 'mp4';
            } else {
                $video_format = 'ogv';
            }

            $cache_file = $cache_dir . '/' . $media['id'] . '_' . $dest_width . 'x' . $dest_height . '.' . $video_format;

            if (!file_exists($cache_file)) {
                $strtr_array = array('{infile}' => $media_file, '{outfile}' => $cache_file, '{width}' => $dest_width, '{height}' => $dest_height);

                if ($video_format == 'mp4') {
                    // resolution apparently needs to be divisible by 2.
                    $dest_width = round($dest_width / 2) * 2;
                    $dest_height = round($dest_height / 2) * 2;
                    exec(strtr(OB_TRANSCODE_VIDEO_MP4, $strtr_array));
                } else {
                    exec(strtr(OB_TRANSCODE_VIDEO_OGV, $strtr_array));
                }
            }

            if ($video_format == 'mp4') {
                header('Content-Type: video/mp4');
            } else {
                header("Content-Type: video/ogg");
            }
            header('Content-Length: ' . filesize($cache_file));
            $fp = fopen($cache_file, 'rb');
            fpassthru($fp);
        } elseif ($type == 'image') {
      // download mode
            if ($download) {
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=' . $download_filename);

                if ($format == 'png') {
                    header('Content-Type: image/png');
                } elseif ($format == 'jpg') {
                    header('Content-Type: image/jpeg');
                } elseif ($format == 'gif') {
                    header('Content-Type: image/gif');
                } elseif ($format == 'svg') {
                    header('Content-Type: image/svg+xml');
                } else {
                    die();
                }

                header('Content-Length: ' . filesize($media_file));

                $fp = fopen($media_file, 'rb');
                fpassthru($fp);
                exit();
            }

            if (!empty($_GET['w']) && !empty($_GET['h']) && preg_match('/^[0-9]+$/', $_GET['w']) && preg_match('/^[0-9]+$/', $_GET['h'])) {
                $dest_width = $_GET['w'];
                $dest_height = $_GET['h'];
            } else {
                $dest_width = 320;
                $dest_height = 240;
            }

            $cache_file = $cache_dir . '/' . $media['id'] . '_' . $dest_width . 'x' . $dest_height . '.jpg';

            if (!file_exists($cache_file)) {
                OBFHelpers::image_resize($media_file, $cache_file, $dest_width, $dest_height);
            }

            if (!file_exists($cache_file)) {
                http_response_code(404);
                exit;
            }

            header('Content-type: image/jpeg');
            header('Content-Length: ' . filesize($cache_file));
            $fp = fopen($cache_file, 'rb');
            fpassthru($fp);
        }

        exit();
    }
}

// default is to preview active media file
$download_mode = false;
$version = false;

// if version set, we are also downloading
if (isset($_GET['v'])) {
    $download_mode = true;
    $version = (int) $_GET['v'];
} elseif (isset($_GET['dl']) && $_GET['dl'] == 1) {
    // or just downloading active media file
    $download_mode = true;
}

$preview = new MediaPreview();
$preview->output($_GET['id'], $download_mode, $version);
