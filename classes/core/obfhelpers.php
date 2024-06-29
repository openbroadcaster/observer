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
        $allow_attr = array('href', 'title', 'alt');

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
            trigger_error('The ImageMagick (imagick) extension is required to resize SVG images.', E_USER_ERROR);
        }

        if ($format == 'svg') {
            $im = new Imagick();
            $svg = file_get_contents($src);
            $im->readImageBlob($svg);

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
        } else {
            $image_data = getimagesize($src);

            list($source_width, $source_height) = $image_data;

            $source_ratio = $source_width / $source_height;
            $ratio = $width / $height;

            if ($ratio > $source_ratio) {
                $width = $height * $source_ratio;
            } else {
                $height = $width / $source_ratio;
            }

            // png or jpg?
            if ($image_data[2] == IMAGETYPE_PNG) {
                $image_source = imagecreatefrompng($src);
            } else {
                $image_source = imagecreatefromjpeg($src);
            }

            $image_dest = imagecreatetruecolor($width, $height);

            imagecopyresampled($image_dest, $image_source, 0, 0, 0, 0, $width, $height, $source_width, $source_height);

            imagejpeg($image_dest, $dst);
            imagedestroy($image_dest);
            imagedestroy($image_source);
        }

        return true;
    }
}
