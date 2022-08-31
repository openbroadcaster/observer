<?php

class OBUpdate20191019 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = 'Media metadata tag updates.';

        return $updates;
    }

    public function run()
    {

    // add media_metadata_tags table

        $this->db->query('CREATE TABLE `media_metadata_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `media_metadata_column_id` int(10) UNSIGNED NOT NULL,
  `tag` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        $this->db->query('ALTER TABLE `media_metadata_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `media_id` (`media_id`),
  ADD KEY `media_metadata_column_id` (`media_metadata_column_id`);');

        $this->db->query('ALTER TABLE `media_metadata_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;');

        $this->db->query('ALTER TABLE `media_metadata_tags`
  ADD CONSTRAINT `media_metadata_tags_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `media_metadata_tags_ibfk_2` FOREIGN KEY (`media_metadata_column_id`) REFERENCES `media_metadata_columns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // copy tags to new table

        $this->db->where('type', 'tags');
        $columns = $this->db->get('media_metadata_columns');

        foreach ($columns as $column) {
            $this->db->what($column['name'], 'tags');
            $this->db->what('media_id', 'media_id');
            $this->db->where_string($this->db->format_table_column($column['name']) . ' IS NOT NULL');
            $rows = $this->db->get('media_metadata');

            foreach ($rows as $row) {
                $tags = explode(',', $row['tags']);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if ($tag === '') {
                        continue;
                    }

                    $this->db->insert('media_metadata_tags', [
                    'media_id' => $row['media_id'],
                    'media_metadata_column_id' => $column['id'],
                    'tag' => $tag
                    ]);
                }
            }
        }

        return true;
    }
}
