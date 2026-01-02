<?php

namespace OpenBroadcaster\Remote;

class ThumbnailAction extends BaseAction
{
    public function run(): bool|object
    {
        $mediaModel = $this->load->model('Media');

        $mediaId = $this->request->media_id ?? null;
        if (!$mediaId) {
            header("HTTP/1.0 404 Not Found");
            die();
        }

        $thumbnailFile = $mediaModel('thumbnail_file', ['media' => $mediaId]);
        if (!$thumbnailFile) {
            header("HTTP/1.0 404 Not Found");
            die();
        }

        // get file mime
        $mime = mime_content_type($thumbnailFile);
        header("Content-Type: $mime");
        header("Content-Length: " . filesize($thumbnailFile));
        readfile($thumbnailFile);

        return true;
    }
}
