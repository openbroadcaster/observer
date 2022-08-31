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

    // **** //

    This file includes code from Valums File Uploader. © 2010 Andrew Valums.
    Licensed under GPL v2 or later.
    See license information in extras/valums-file-uploader.
*/

require('components.php');

// COMPLETE AUTHENTICATION, usually handled by api.php
$user = OBFUser::get_instance();

$auth_id = null;
$auth_key = null;

// try to get an ID/key pair for user authorization.
if (!empty($_POST['i']) && !empty($_POST['k'])) {
    $auth_id = $_POST['i'];
    $auth_key = $_POST['k'];
}

/*
// disabled, should no longer be used.
// if not in post, try fetching from cookie.
elseif(!empty($_COOKIE['ob_auth_id']) && !empty($_COOKIE['ob_auth_key']))
{
  $auth_id = $_COOKIE['ob_auth_id'];
  $auth_key = $_COOKIE['ob_auth_key'];
}
*/

// this is another comment
if (empty($_POST['appkey'])) {
    $user->auth($auth_id, $auth_key);
} else {
    $user->auth_appkey($_POST['appkey'], [['media','save']]);
}

// define our class, create instance, handle upload.
class Upload extends OBFController
{
    // used by handle_upload() to get some important information about the uploaded media
    private function media_info($filename)
    {
        // $media_model = $this->load->model('Media');
        // return $media_model('media_info',$filename);
        return $this->models->media('media_info', ['filename' => $filename]);
    }


    public function handle_upload()
    {

    // max file size in bytes
        // $sizeLimit = 100 * 1024 * 1024;
        $models = OBFModels::get_instance();

        $key = $this->randKey();
        $id = $this->db->insert('uploads', array('key' => $key, 'expiry' => strtotime('+24 hours')));

        $input = fopen("php://input", "r");
        $target = fopen(OB_ASSETS . '/uploads/' . $id, "w");
        $realSize = stream_copy_to_stream($input, $target);
        fclose($input);
        fclose($target);

        if ($realSize != (int) $_SERVER["CONTENT_LENGTH"]) {
            echo json_encode(array('error' => 'File upload was not successful.  Please try again.'));
            unlink(OB_ASSETS . '/uploads/' . $id);
            return;
        }

        // make sure not too big. filesize limit in MB, default 1024.
        if (($realSize / 1024 / 1024) > OB_MEDIA_FILESIZE_LIMIT) {
            echo json_encode(array('error' => 'File too large (max size 1GB).'));
            unlink(OB_ASSETS . '/uploads/' . $id);
            return;
        }

        $result['file_id'] = $id;
        $result['file_key'] = $key;

        // get ID3 data.
        $id3_data = $models->media('getid3', ['filename' => OB_ASSETS . '/uploads/' . $id]);

        // $result['info'] = array('comments'=>$id3['comments']);

        // get only the data we need (this should be expanded). sometimes other data causes encoding problems? maybe re: thumbnail image.
        /*$id3_data = array();
        if(isset($id3['comments']['artist'])) $id3_data['artist'] = $id3['comments']['artist'];
        if(isset($id3['comments']['album'])) $id3_data['album'] = $id3['comments']['album'];
        if(isset($id3['comments']['title'])) $id3_data['title'] = $id3['comments']['title'];
        if(isset($id3['comments']['comments'])) $id3_data['comments'] = $id3['comments']['comments'];*/
        if (count($id3_data) > 0) {
            $result['info'] = array('comments' => $id3_data);
        } else {
            $result['info'] = array();
        }

        // get some useful media information, insert it into the db with our file id/key.
        $media_info = $this->media_info(OB_ASSETS . '/uploads/' . $id);
        $this->db->where('id', $id);
        $this->db->update('uploads', array('format' => $media_info['format'], 'type' => $media_info['type'], 'duration' => $media_info['duration']));

        $result['media_info'] = $media_info;

        $result['media_supported'] = $models->media('format_allowed', ['type' => $media_info['type'], 'format' => $media_info['format']]);

        // to pass data through iframe you will need to encode all html tags
        echo json_encode($result);
    }

    private function randKey()
    {
        $chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789';
        $key = '';
        for ($i = 0; $i < 16; $i++) {
            $key .= $chars[rand(0, (strlen($chars) - 1))];
        }
        return $key;
    }
}

$upload = new Upload();
$upload->handle_upload();
