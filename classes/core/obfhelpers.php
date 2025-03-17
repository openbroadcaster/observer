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
 * OpenBroadcaster helper functions.
 *
 * @package Class
 */
class OBFHelpers
{
    public static function &get_instance()
    {
        static $instance;
        if (isset($instance)) {
            return $instance;
        }
        $instance = new OBFHelpers();
        return $instance;
    }

    /**
     * Sanitize HTML, allowing only specific tags and attributes from user input
     * and stripping out everything else.
     *
     * @param html
     *
     * @return sanitized_html
     */
    public static function sanitize_html($html)
    {
        $allow_tags = "<p><br><strong><b><u><ul><ol><li><a>";
        $allow_attr = ['href', 'title', 'alt'];

        $html = strip_tags($html, $allow_tags);
        $result = new DOMDocument();
        if ($html == '') {
            return '';
        }
        $result->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        foreach ($result->getElementsByTagName('*') as $element) {
            foreach ($element->attributes as $attr) {
                if (!in_array($attr->name, $allow_attr)) {
                    $element->removeAttributeNode($attr);
                }
            }
        }

        $result = $result->saveHTML();
        return $result;
    }

    /**
     * Require specific named arguments in arrays passed to model methods. If
     * any of the required arguments aren't passed, throw an error using
     * user_error.
     *
     * @param args The array of arguments to check.
     * @param reqs The required arguments, in the format ['req1', 'req2', ...]
     */
    public static function require_args($args, $reqs)
    {
        if (!is_array($args)) {
            user_error('Passed arguments should be an array.');
        }
        if (!is_array($reqs)) {
            user_error('Required arguments should be an array.');
        }

        foreach ($reqs as $req) {
            if (!isset($args[$req])) {
                user_error('Missing argument: ' . $req);
            }
        }
    }

    /**
     * Get list of arguments passed to a model method as reference, then set
     * any arguments to the default if not specified.
     *
     * @param args A reference to the array of arguments to check.
     * @param defs The default values in case an argument is not set, in the format ['def1' => defval1, 'def2' => defval2, ...]
     */
    public static function default_args(&$args, $defs)
    {
        if (!is_array($args)) {
            user_error('Passed arguments should be an array.');
        }
        if (!is_array($defs)) {
            user_error('Default arguments should be an array.');
        }

        foreach ($defs as $def => $defval) {
            if (!isset($args[$def])) {
                $args[$def] = $defval;
            }
        }
    }

    /**
     * Determine image format.
     *
     * @param filename Image filename.
     */
    public static function image_format($filename)
    {
        if (!file_exists($filename)) {
            trigger_error('This file does not exist', E_USER_WARNING);
            return false;
        }

        $mime_type = mime_content_type($filename);
        switch ($mime_type) {
            case 'image/svg+xml':
                return 'svg';
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/webp':
                return 'webp';
            case 'image/tiff':
                return 'tif';
        }

        // backup in case mime type failed
        $gd_type = getimagesize($filename);
        if (isset($gd_type[2])) {
            switch ($gd_type[2]) {
                case IMAGETYPE_JPEG:
                    return 'jpg';
                case IMAGETYPE_PNG:
                    return 'png';
            }
        }

        // no result
        return false;
    }

    /**
     * Resize an image.
     *
     * @param src Source filename.
     * @param dst Destination filename (JPEG).
     * @param width Target width.
     * @param height Target height.
     */
    public static function image_resize($src, $dst, $width, $height, $rotate = 0)
    {
        if (!file_exists($src)) {
            trigger_error('The source file does not exist', E_USER_WARNING);
            return false;
        }

        // get destination directory and create if it doesn't exist
        $dst_pathinfo = pathinfo($dst);
        if (!is_dir($dst_pathinfo['dirname'])) {
            mkdir($dst_pathinfo['dirname'], 0777, true);
            echo $dst_pathinfo['dirname'];
        }

        // make sure it exists now
        if (!is_dir($dst_pathinfo['dirname'])) {
            echo 'foo';
            trigger_error('Unable to create destination directory.', E_USER_WARNING);
            return false;
        }

        // and we can write to it
        if (!is_writeable($dst_pathinfo['dirname'])) {
            echo 'bar';
            trigger_error('The destination directory is not writeable', E_USER_WARNING);
            return false;
        }

        // figure out image format
        $format = OBFHelpers::image_format($src);
        if (!$format) {
            trigger_error('Unable to determine image format', E_USER_WARNING);
            return false;
        }

        if ($format == 'svg' && !extension_loaded('imagick')) {
            trigger_error('The ImageMagick (imagick) extension is required.', E_USER_ERROR);
        }

        $im = new Imagick();
        $im->readImage($src);
        $im->setImageBackgroundColor('white'); // Set white background if necessary
        $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE); // Remove alpha channel
        $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN); // Flatten image
        $im->rotateImage('white', $rotate);

        $im->thumbnailImage($width, $height, true); // Adjust width and height as necessary (maintain aspect ratio)

        // Set the image format and apply lossy compression
        $im->setImageFormat('webp');
        $im->setOption('webp:lossless', 'true'); // For lossless
        $im->setOption('webp:quality', '85'); // Set quality (0-100) for lossy compression

        // write image
        $im->writeImage($dst);
        $im->clear();
        $im->destroy();

        return true;
    }

    /**
     * Generate thumbnail from the first page of a PDF.
     *
     * @param src Source filename.
     * @param dst Destination filename (JPEG).
     * @param width Target width.
     * @param height Target height.
     */
    public static function pdf_thumbnail($src, $dst, $width, $height)
    {
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
            escapeshellarg($src)
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
            $im = new Imagick();
            $im->readImage($tempFileTiff);
            $im->setImageBackgroundColor('white'); // Set white background
            $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE); // Remove alpha channel
            $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN); // Flatten image
            $im->thumbnailImage($width, $height, true);
            $im->setImageFormat('jpeg');
            $im->setImageCompression(Imagick::COMPRESSION_JPEG);
            $im->setImageCompressionQuality(90);
            $im->writeImage($dst);
            $im->clear();
            $im->destroy();

            // Remove the temporary file
            unlink($tempFileTiff);

            return true;
        } catch (ImagickException $e) {
            error_log("ImageMagick error: " . $e->getMessage());
            if (file_exists($tempFileTiff)) {
                unlink($tempFileTiff);
            }
            return false;
        }
    }

    /**
     * Send file directly with server (if possible) to avoid keeping PHP process open, memory limit
     * issues, etc.
     *
     * @param file
     * @param type Optional MIME type. Default NULL.
     * @param download Optional download flag. Default false.
     */
    public static function sendfile($file, $type = null, $download = false)
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
        } else {
            readfile($file);
        }

        die();
    }

    /**
     * Get the media file path.
     *
     * @param media
     */
    public static function media_file($media)
    {
        if (is_string($media)) {
            $media = intval($media);
        }

        if (is_int($media)) {
            $models = OBFModels::get_instance();
            $media = $models->media('get_by_id', ['id' => $media]);
        }

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
     * Authorize access to media preview, triggering an error if no access.
     *
     * @param media
     */
    public static function preview_media_auth($media)
    {
        if (is_string($media)) {
            $media = intval($media);
        }

        if (is_int($media)) {
            $models = OBFModels::get_instance();
            $media = $models->media('get_by_id', ['id' => $media]);
        }

        $userInstance = OBFUser::get_instance();

        // check permissions
        if ($media['status'] != 'public') {
            $userInstance->require_authenticated();
            $is_media_owner = $media['owner_id'] == $userInstance->param('id');
            if ($media['status'] == 'private' && !$is_media_owner) {
                $userInstance->require_permission('manage_media');
            }
        }
    }

    /**
     * Authorize access to media download, triggering an error if no access.
     *
     * @param media
     */
    public static function download_media_auth($media)
    {
        if (is_string($media)) {
            $media = intval($media);
        }

        if (is_int($media)) {
            $models = OBFModels::get_instance();
            $media = $models->media('get_by_id', ['id' => $media]);
        }

        $userInstance = OBFUser::get_instance();

        // check permissions
        if ($media['status'] != 'public') {
            $userInstance->require_authenticated();
            $is_media_owner = $media['owner_id'] == $userInstance->param('id');

            // download requires download_media if this is not the media owner
            if (!$is_media_owner) {
                $userInstance->require_permission('download_media');
            }

            // private media requires manage_media if this is not the media owner
            if ($media['status'] == 'private' && !$is_media_owner) {
                $userInstance->require_permission('manage_media');
            }
        }
    }
}
