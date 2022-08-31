<?php

class OBUpdate20190929 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = 'Set default media settings if not already set.';

        return $updates;
    }

    public function run()
    {

    // set dynamic content field default if not already set
        $this->db->where('name', 'dynamic_content_field');
        $setting = $this->db->get_one('settings');
        if (!$setting) {
            $this->db->insert('settings', [
            'name' => 'dynamic_content_field',
            'value' => '{"default":"disabled","hidden":false}'
            ]);
        }

        // set media metadata settings if not already set
        $this->db->where('name', 'core_metadata');
        $setting = $this->db->get_one('settings');
        if (!$setting) {
            $this->db->insert('settings', [
            'name' => 'core_metadata',
            'value' => '{"artist":"required","album":"enabled","year":"enabled","category_id":"required","country_id":"required","language_id":"required","comments":"enabled"}'
            ]);
        }

        return true;
    }
}
