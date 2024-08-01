<?php

class OBUpdate20240731 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Thumbnails directory structure updates.';
        return $updates;
    }

    // convert path info to the 2-digit/letter location code
    private function location($pathinfo)
    {
        $location = realpath($pathinfo['dirname']);
        $location = explode('/', $location);
        $location = array_slice($location, -2);

        if (count($location) != 2 || !preg_match('/^[0-9A-Z]{1}$/', $location[0]) || !preg_match('/^[0-9A-Z]{1}$/', $location[1])) {
            return false;
        }

        return[$location[0], $location[1]];
    }

    public function run()
    {
        if (!is_dir(OB_CACHE . '/thumbnails')) {
            mkdir(OB_CACHE . '/thumbnails');
        }

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(OB_THUMBNAILS));
        $thumbnails = [];
        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            $pathinfo = pathinfo($file->getPathname());
            $location = $this->location($pathinfo);
            if (!$location) {
                continue;
            }

            // make sure filename is a number (id)
            if (!preg_match('/^\d+$/', $pathinfo['filename'])) {
                continue;
            }

            if (!isset($thumbnails[$location[0]])) {
                $thumbnails[$location[0]] = [];
            }

            if (!isset($thumbnails[$location[0]][$location[1]])) {
                $thumbnails[$location[0]][$location[1]] = [];
            }

            $thumbnails[$location[0]][$location[1]][] = $pathinfo['filename'];
        }

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(OB_CACHE . '/thumbnails'));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            $pathinfo = pathinfo($file->getPathname());
            $location = $this->location($pathinfo);
            if (!$location) {
                continue;
            }

            // make sure filename is a number (id)
            if (!preg_match('/^\d+$/', $pathinfo['filename'])) {
                continue;
            }

            // check if we have this thumbnail already
            if (isset($thumbnails[$location[0]][$location[1]]) && in_array($pathinfo['filename'], $thumbnails[$location[0]][$location[1]])) {
                continue;
            }

            // copy over to thumbnail directory
            $newpath = OB_THUMBNAILS . '/' . $location[0] . '/' . $location[1] . '/' . $pathinfo['filename'] . '.' . $pathinfo['extension'];
            if (!is_dir(dirname($newpath))) {
                mkdir(dirname($newpath), 0777, true);
            }
            copy($file->getPathname(), $newpath);

            // checksum the original and destination to verify
            $checksum1 = md5_file($file->getPathname());
            $checksum2 = md5_file($newpath);
            var_dump($checksum1);
            var_dump($checksum2);
            if ($checksum1 != $checksum2) {
                echo 'Failed to copy ' . $file->getPathname() . ' to ' . $newpath . PHP_EOL;
                return false;
            }
        }

        return true;
    }
}
