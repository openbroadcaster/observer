<?php

class OBUpdate20210104 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Add playlist item properties column for crossfade and other properties.";
        $updates[] = "Database structure cleanup.";
        $updates[] = "Note: This update will clear schedule cache (causing shows with dynamic playlists to be regenerated).";
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `playlists_items` ADD `properties` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `dynamic_query`;');

        $this->db->what('playlists_items.*');
        $this->db->what('media.type', 'media_type');
        $this->db->leftjoin('media', 'playlists_items.item_id', 'media.id');
        $items = $this->db->get('playlists_items');

        foreach ($items as $item) {
            if ($item['item_type'] == 'media' && $item['media_type'] == 'image') {
                $this->db->where('id', $item['id']);
                $this->db->update('playlists_items', ['properties' => json_encode(['duration' => (float)$item['duration']])]);
            }

            if ($item['item_type'] == 'dynamic') {
                $this->db->where('id', $item['id']);
                $this->db->update('playlists_items', ['properties' => json_encode(
                    [
                    'name' => $item['dynamic_name'],
                    'num_items' => (int) $item['dynamic_num_items'],
                    'image_duration' => (int) $item['dynamic_image_duration'],
                    'query' => json_decode($item['dynamic_query'], true)
                    ]
                )]);
            }

            if ($item['item_type'] == 'custom') {
                $this->db->where('id', $item['id']);
                $this->db->update('playlists_items', ['properties' => $item['dynamic_query']]);
            }
        }

        $this->db->query('ALTER TABLE `playlists_items` DROP `duration`;');
        $this->db->query('ALTER TABLE `playlists_items` DROP `dynamic_name`;');
        $this->db->query('ALTER TABLE `playlists_items` DROP `dynamic_num_items`;');
        $this->db->query('ALTER TABLE `playlists_items` DROP `dynamic_image_duration`;');
        $this->db->query('ALTER TABLE `playlists_items` DROP `dynamic_query`;');
        $this->db->query('TRUNCATE schedules_media_cache;');

        if ($this->db->error()) {
            return false;
        }
        return true;
    }
}
