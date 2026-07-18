<?php

class OBUpdate20260717 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Fix country_id still being in core_metadata setting if it's not been manually updated.";
        return $updates;
    }

    public function run()
    {
        $this->db->query("SELECT * FROM settings WHERE name = 'core_metadata'");
        if ($this->db->error()) {
            echo $this->db->error() . PHP_EOL;
            return false;
        }

        $settingJson = $this->db->assoc_list()[0]['value'] ?? null;

        if (! $settingJson) {
            echo 'Failed to load core metadata settings.' . PHP_EOL;
            return false;
        }

        $setting = json_decode($settingJson, true);
        if (! $setting) {
            echo 'Invalid JSON in core metadata settings.' . PHP_EOL;
            return false;
        }

        if (! isset($setting['country_id']) && ! isset($setting['country'])) {
            // Neither metadata is set, this should not be possible, so something must've gone wrong in decoding.
            echo 'Failed to decode either old country_id or new country metadata setting.' . PHP_EOL;
            return false;
        }

        if (isset($setting['country_id']) && ! isset($setting['country'])) {
            // Old country_id still used, but no country set, so change that.
            $setting['country'] = $setting['country_id'];
        }

        if (isset($setting['country_id'])) {
            // Unset the country_id if it was still set for some reason (should only be from never saving settings
            // after previous, but just in case there's other situations).
            unset($setting['country_id']);
        }

        $settingJson = json_encode($setting);
        $this->db->where('name', 'core_metadata');
        $this->db->update('settings', [
            'value' => $settingJson,
        ]);
        if ($this->db->error()) {
            echo $this->db->error() . PHP_EOL;
            return false;
        }

        return true;
    }
}
