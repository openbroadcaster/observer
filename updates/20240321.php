<?php

class OBUpdate20240321 extends OBUpdate
{
    public function items()
    {
        $updates = [];

        $updates[] = "Add metadata columns to media table directly.";
        $updates[] = "Move metadata rows from media_metadata to media tables.";
        $updates[] = "Rename metadata tables for future code changes.";

        return $updates;
    }

    public function run()
    {
        // Add metadata columns to media table directly.
        $metadataColumns = $this->models->mediametadata('get_all');
        foreach ($metadataColumns as $metadataColumn) {
            switch ($metadataColumn['type']) {
                case 'text':
                    $colType = 'VARCHAR(255)';
                    break;
                case 'textarea':
                    $colType = 'TEXT';
                    break;
                case 'integer':
                    $colType = 'BIGINT(20)';
                    break;
                case 'bool':
                    $colType = 'TINYINT(1)';
                    break;
                case 'select':
                    $colType = 'VARCHAR(255)';
                    break;
                case 'tags':
                    // Tags are stored in media_tags table.
                    continue;
                    break;
                case 'hidden':
                    $colType = 'LONGTEXT';
                    break;
                default:
                    echo "Unknown metadata type: {$metadataColumn['type']}";
                    return false;
            }
            $this->db->query("ALTER TABLE media ADD COLUMN `metadata_{$metadataColumn['name']}` {$colType} DEFAULT NULL;");
            if ($this->db->error()) {
                echo $this->db->error();
                return false;
            }
        }

        // Move metadata rows from media_metadata to media tables.
        $this->db->query("SELECT * FROM media_metadata;");
        if ($this->db->error()) {
            echo $this->db->error();
            return false;
        }

        foreach ($this->db->assoc_list() as $mediaItem) {
            $mediaId = $mediaItem['media_id'];

            $data = [];
            foreach ($metadataColumns as $metadataColumn) {
                $metadataName = $metadataColumn['name'];
                $metadataValue = $mediaItem[$metadataName];
                $data['metadata_' . $metadataName] = $metadataValue;
            }

            $this->db->where('id', $mediaId);
            $this->db->update('media', $data);
            if ($this->db->error()) {
                echo $this->db->error();
                return false;
            }
        }

        // Rename metadata tables for future code changes.
        $this->db->query("RENAME TABLE media_metadata TO _media_metadata;");
        if ($this->db->error()) {
            echo $this->db->error();
            return false;
        }

        $this->db->query("RENAME TABLE media_metadata_columns TO media_metadata;");
        if ($this->db->error()) {
            echo $this->db->error();
            return false;
        }

        $this->db->query("RENAME TABLE media_metadata_tags TO media_tags;");
        if ($this->db->error()) {
            echo $this->db->error();
            return false;
        }

        return true;
    }
}
