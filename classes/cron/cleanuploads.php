<?php

namespace OB\Classes\Cron;

use OB\Classes\Base\Cron;

class CleanUploads extends Cron
{
    public function interval(): int
    {
        return 300;
    }

    public function run(): bool
    {
        if (!defined('OB_ASSETS') || !is_dir(OB_ASSETS . '/uploads')) {
            return false;
        }

        $uploadsDir = OB_ASSETS . '/uploads/';

        $db = \OBFDB::get_instance();
        $db->where('expiry', time(), '<');
        $uploads = $db->get('uploads');

        foreach ($uploads as $upload) {
            unlink($uploadsDir . $upload['id']);
            $db->where('id', $upload['id']);
            $db->delete('uploads');
        }

        // TODO remove any files that are not in the database?

        return true;
    }
}
