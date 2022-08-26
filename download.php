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

date_default_timezone_set('Etc/UTC');

class ObDownload
{
    private $db;

    public function __construct()
    {
        $this->db = OBFDB::get_instance();
        $this->download();
    }

    public function download()
    {
        $media_id = $_GET['media_id'];

        if (!$media_id) {
            return false;
        }

        $this->db->where('id', $media_id);
        $media = $this->db->get_one('media');
        if (empty($media)) {
            die();
        }

        if ($media['status'] != 'public') {
            die();
        }

        if ($media['is_archived']==1) {
            $filedir=OB_MEDIA_ARCHIVE;
        } elseif ($media['is_approved']==0) {
            $filedir=OB_MEDIA_UPLOADS;
        } else {
            $filedir=OB_MEDIA;
        }

        $filedir.='/'.$media['file_location'][0].'/'.$media['file_location'][1];

        $fullpath=$filedir.'/'.$media['filename'];

        if (!file_exists($fullpath)) {
            die();
        }

        header("Access-Control-Allow-Origin: *");
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($fullpath));
        header('Content-Disposition: attachment; filename="'.$media['filename'].'"');

        readfile($fullpath);
    }
}

$download = new ObDownload();
