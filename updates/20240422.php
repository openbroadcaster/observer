<?php

class OBUpdate20240422 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Change hidden type metadata fields into text type fields with mode set to hidden.';

        return $updates;
    }

    public function run()
    {
        $this->db->where('type', 'hidden');
        $fields = $this->db->get('media_metadata');
        if ($this->db->error()) {
            echo 'Failed to query media_metadata table.';
            return false;
        }

        foreach ($fields as $field) {
            $settings = json_decode($field['settings'], true);
            $settings['mode'] = 'hidden';

            $this->db->where('id', $field['id']);
            $this->db->update('media_metadata', [
                'type'     => 'text',
                'settings' => json_encode($settings),
            ]);
            if ($this->db->error()) {
                echo 'Failed to update metadata field ' . $field['name'] . '.';
                return false;
            }
        }

        return true;
    }
}
