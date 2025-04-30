<?php

namespace OpenBroadcaster\Remote;

class MediaAction extends BaseAction
{
    private $PlayersModel;

    public function __construct(object $player, object $request)
    {
        parent::__construct($player, $request);
    }

    public function run(): bool|object
    {
        if (empty($this->request->media_id)) {
            die();
        }

        $this->db->where('id', $this->request->media_id);
        $media = $this->db->get_one('media');
        if (empty($media)) {
            die();
        }

        if ($media['is_archived'] == 1) {
            $filedir = OB_MEDIA_ARCHIVE;
        } elseif ($media['is_approved'] == 0) {
            $filedir = OB_MEDIA_UPLOADS;
        } else {
            $filedir = OB_MEDIA;
        }

        $filedir .= '/' . $media['file_location'][0] . '/' . $media['file_location'][1];

        $fullpath = $filedir . '/' . $media['filename'];

        if (!file_exists($fullpath)) {
            die();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($fullpath));

        readfile($fullpath);

        return true;
    }
}
