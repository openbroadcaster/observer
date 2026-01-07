<?php

class OBUpdate20190918 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = 'Media stream and thumbnail version support.';

        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `media` ADD `stream_version` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `dynamic_select`, ADD `thumbnail_version` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `stream_version`;');

        // check if media_streams exists, early return if it doesn't exist.
        $this->db->query('SHOW TABLES LIKE "media_streams"');
        if ($this->db->num_rows() < 1) {
            return true;
        }

        // copy data from streams table to media table
        $streams = $this->db->get('media_streams');
        foreach ($streams as $stream) {
            $this->db->where('id', $stream['media_id']);
            $this->db->update('media', ['stream_version' => $stream['version']]);
        }

        // remove media streams table which is no longer used
        $this->db->query('DROP TABLE `media_streams`');

        return true;
    }
}
