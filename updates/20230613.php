<?php

class OBUpdate20230613 extends OBUpdate
{
    public function items()
    {
        $updates = [];

        $updates[] = 'Add a file location column to playlists to use when storing thumbnails.';
        $updates[] = 'Add a random location to all previously existing playlists and create their directories.';
        $updates[] = 'Create thumbnail directories for all media file locations.';

        return $updates;
    }

    public function run()
    {
        // Add a file location column to playlists to use when storing thumbnails.
        $this->db->query('ALTER TABLE `playlists` ADD COLUMN `file_location` VARCHAR(2) NOT NULL AFTER `name`;');
        if ($this->db->error()) {
            return false;
        }

        // Add a random location to all previously existing playlists and create their directories.
        $playlists = $this->db->get('playlists');
        if (! $playlists) {
            return false;
        }

        foreach ($playlists as $playlist) {
            if ($playlist['file_location'] !== '') {
                continue;
            }

            $file_location = $this->rand_file_location();
            $this->db->where('id', $playlist['id']);
            $this->db->update('playlists', [
                'file_location' => $file_location
            ]);

            if ($this->db->error()) {
                return false;
            }
        }

        // Create thumbnail directories for all media file locations.
        $media = $this->db->get('media');
        if (! $media) {
            return false;
        }

        foreach ($media as $media_item) {
            if (strlen($media_item['file_location']) !== 2) {
                continue; // This shouldn't happen, but the update shouldn't blow up over it.
            }

            $requiredDirs = [
                OB_THUMBNAILS . '/media',
                OB_THUMBNAILS . '/media/' . $media_item['file_location'][0],
                OB_THUMBNAILS . '/media/' . $media_item['file_location'][0] . '/' . $media_item['file_location'][1]
            ];

            foreach ($requiredDirs as $checkDir) {
                if (! file_exists($checkDir)) {
                    mkdir($checkDir);
                }
            }
        }

        return true;
    }

    private function rand_file_location()
    {
        $charSelect = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $randVal = rand(0, 1295);

        $randValA = $randVal % 36;
        $randValB = ($randVal - $randValA) / 36;

        $charA = $charSelect[$randValA];
        $charB = $charSelect[$randValB];

        $requiredDirs = array();
        $requiredDirs[] = OB_THUMBNAILS . '/playlist';
        $requiredDirs[] = OB_THUMBNAILS . '/playlist/' . $charA;
        $requiredDirs[] = OB_THUMBNAILS . '/playlist/' . $charA . '/' . $charB;

        foreach ($requiredDirs as $checkDir) {
            if (! file_exists($checkDir)) {
                mkdir($checkDir);
            }
        }

        return $charA . $charB;
    }
}
