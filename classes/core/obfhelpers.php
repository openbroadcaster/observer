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
    public static function image_resize($src, $dst, $width, $height)
    {
        if (!file_exists($src)) {
            trigger_error('The source file does not exist', E_USER_WARNING);
            return false;
        }
        if (!is_writeable(pathinfo($dst)['dirname'])) {
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
        $imgdata = file_get_contents($src);
        $im->readImageBlob($imgdata);

        $source_width = $im->getImageWidth();
        $source_height = $im->getImageHeight();
        $source_ratio = $source_width / $source_height;
        $ratio = $width / $height;

        if ($ratio > $source_ratio) {
            $width = $height * $source_ratio;
        } else {
            $height = $width / $source_ratio;
        }

        $im->setImageFormat("jpeg");
        $im->adaptiveResizeImage($width, $height);

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
}
