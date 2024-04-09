<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Secondary model for managing media metadata.
 *
 * @package Model
 */
class MediaMetadataModel extends OBFModel
{
    /**
     * Get all metadata columns.
     *
     * @return metadata_columns
     */
    public function get_all()
    {
        $this->db->orderby('order_id');
        $fields = $this->db->get('media_metadata');
        foreach ($fields as &$field) {
            if (!$field['settings']) {
                $field['settings'] = '{}';
            }
            $field['settings'] = json_decode($field['settings']);

            // attach all available tags to tag settings
            if ($field['type'] == 'tags') {
                $field['settings']->all = $this('tag_search', ['id' => $field['id'],'search' => '']);
            }
        }
        return $fields;
    }

    /**
     * Get a metadata column.
     *
     * @param id
     *
     * @param metadata_column
     */
    public function get_one($id)
    {
        $this->db->where('id', $id);
        $field = $this->db->get_one('media_metadata');
        if ($field['settings']) {
            $field['settings'] = json_decode($field['settings']);
        }
        return $field;
    }

    /**
     * Get a metadata column by name.
     *
     * @param name
     *
     * @param metadata_column
     */
    public function get_by_name($name)
    {
        $this->db->where('name', $name);
        $field = $this->db->get_one('media_metadata');
        if ($field['settings']) {
            $field['settings'] = json_decode($field['settings']);
        }
        return $field;
    }

    /**
     * Save a new order for metadata fields.
     *
     * @param order An array of metadata column IDs in the preferred order.
     *
     * @return is_valid_order
     */
    public function save_field_order($order)
    {
        if (!is_array($order)) {
            return false;
        }

        // make sure all valid before saving
        $expected_order_id = 0;
        foreach ($order as $order_id => $field_id) {
            if (!$this->get_one($field_id) || $order_id != $expected_order_id) {
                return false;
            }
            $expected_order_id++;
        }

        // save
        foreach ($order as $order_id => $field_id) {
            $this->db->where('id', $field_id);
            $this->db->update('media_metadata', ['order_id' => $order_id]);
        }

        return true;
    }

    /**
     * Validate metadata field before updating.
     *
     * @param data
     * @param id Optional. Specified when updating an existing metadata field.
     *
     * @return [is_valid, msg]
     */
    public function validate($data, $id)
    {
        // if editing, use name/type from existing field.
        if ($id) {
            $existing_field = $this->get_one($id);
            $data['name'] = $existing_field['name'];
            $data['type'] = $existing_field['type'];
        }

        //T All fields are required.
        if (!$data['name'] || !$data['description'] || !$data['type'] || ($data['type'] == 'select' && !$data['select_options'])) {
            return [false,'All fields are required.'];
        }
        //T Field name must contain only letters, numbers, and underscores.
        if (!preg_match('/^[0-9a-z_]+$/', $data['name'])) {
            return [false,'Field name must contain only letters, numbers, and underscores.'];
        }
        //T Field name maximum length is 32 characters.
        if (strlen($data['name']) > 32) {
            return [false,'Field name maximum length is 32 characters.'];
        }
        //T This field name is reserved and cannot be used.
        if ($data['name'] == 'media_id') {
            return [false,'This field name is reserved and cannot be used.'];
        }

        if (!$id) {
            $this->db->where('name', $data['name']);
            //T This field name is already in use.
            if ($this->db->get_one('media_metadata_columns')) {
                return [false,'This field name is already in use'];
            }
        }

        //T The field type is not valid.
        if (array_search($data['type'], ['select','bool','text','textarea','integer','tags','hidden']) === false) {
            return [false,'The field type is not valid.'];
        }

        return [true,'Valid.'];
    }

    /**
     * Save a metadata field.
     *
     * @param data
     * @param id Optional. Specified when updating an existing metadata field.
     *
     * @return id
     */
    public function save($data, $id)
    {
        $save = [];
        $save['settings'] = [];
        $save['description'] = $data['description'];

        // if editing, use name/type from existing field.
        if ($id) {
            $existing_field = $this->get_one($id);
            $data['name'] = $existing_field['name'];
            $data['type'] = $existing_field['type'];
        }

        // some extra things for select field
        if ($data['type'] == 'select') {
            $options = explode(PHP_EOL, $data['select_options']);
            $save_options = [];
            foreach ($options as $option) {
                $option = trim($option);
                if ($option != '') {
                    $save_options[] = $option;
                }
            }
            $save['settings']['options'] = $save_options;
        }

        // default
        $save['settings']['default'] = $data['default'];

        // tag suggestions
        if ($data['type'] == 'tags') {
            $save['settings']['suggestions'] = [];
            foreach ($data['tag_suggestions'] as $tag) {
                $tag = trim($tag);
                if ($tag != '') {
                    $save['settings']['suggestions'][] = $tag;
                }
            }
            if (is_array($save['settings']['default'])) {
                $save['settings']['suggestions'] = array_values(array_unique(array_merge($save['settings']['suggestions'], $save['settings']['default'])));
            }
        }

        $save['settings'] = json_encode($save['settings']);

        if ($id) {
            $this->db->where('id', $id);
            return $this->db->update('media_metadata', $save);
        } else {
            $save['name'] = $data['name'];
            $save['type'] = $data['type'];

            $id = $this->db->insert('media_metadata', $save);

            if ($save['type'] == 'bool') {
                $this->db->query('ALTER TABLE ' . $this->db->format_backticks('media') . ' ADD ' . $this->db->format_backticks('metadata_' . $data['name']) . ' BOOLEAN NULL DEFAULT NULL');
            } elseif ($save['type'] == 'select' || $save['type'] == 'text') {
                $this->db->query('ALTER TABLE ' . $this->db->format_backticks('media') . ' ADD ' . $this->db->format_backticks('metadata_' . $data['name']) . ' VARCHAR(255) NULL DEFAULT NULL');
            } elseif ($save['type'] == 'textarea' || $save['type'] == 'tags') {
                $this->db->query('ALTER TABLE ' . $this->db->format_backticks('media') . ' ADD ' . $this->db->format_backticks('metadata_' . $data['name']) . ' TEXT NULL DEFAULT NULL');
            } elseif ($save['type'] == 'integer') {
                $this->db->query('ALTER TABLE ' . $this->db->format_backticks('media') . ' ADD ' . $this->db->format_backticks('metadata_' . $data['name']) . ' BIGINT NULL DEFAULT NULL');
            } elseif ($save['type'] == 'hidden') {
                $this->db->query('ALTER TABLE ' . $this->db->format_backticks('media') . ' ADD ' . $this->db->format_backticks('metadata_' . $data['name']) . ' LONGTEXT NULL DEFAULT NULL');
            }

            return $id;
        }
    }

    /**
     * Delete a metadata field.
     *
     * @param id
     *
     * @return was_deleted
     */
    public function delete($id)
    {
        $field = $this->get_one($id);
        if (!$field) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->delete('media_metadata');
        $this->db->query('ALTER TABLE ' . $this->db->format_backticks('media') . ' DROP COLUMN metadata_' . $this->db->format_backticks($field['name']));

        return true;
    }

    /**
     * Validate metadata fields. Making sure that all the mandatory fields are set,
     * and that metadata has no invalid associated values.
     *
     * @param data
     */
    public function validate_fields($data)
    {
        if (
            !array_key_exists('artist', $data) ||
            !array_key_exists('album', $data) ||
            !array_key_exists('year', $data) ||
            !array_key_exists('category_id', $data) ||
            !array_key_exists('country', $data) ||
            !array_key_exists('language', $data) ||
            !array_key_exists('comments', $data) ||
            !array_key_exists('dynamic_content_default', $data) ||
            !array_key_exists('dynamic_content_hidden', $data)
        ) {
            return [false, 'Not all values set in field settings.'];
        }

        foreach ($data as $key => $item) {
            if ($key != 'dynamic_content_hidden' && $item != 'required' && $item != 'enabled' && $item != 'disabled') {
                return [false, 'Invalid value in field settings.'];
            }
        }

        return [true, 'Field settings validated.'];
    }

    /**
     * Get metadata field settings, which fields are required/enabled/disabled, and
     * dynamic content field settings.
     *
     * @return field_settings
     */
    public function get_fields()
    {
        $this->db->where('name', 'core_metadata');
        $data = $this->db->get_one('settings');

        if (!$data) {
            return [false, 'Failed to load field settings from database.'];
        }

        $data = json_decode($data['value'], true);

        $this->db->where('name', 'dynamic_content_field');
        $dynamic_content = $this->db->get_one('settings');
        if ($dynamic_content) {
            $dynamic_content = json_decode($dynamic_content['value'], true);
            $data['dynamic_content_default'] = $dynamic_content['default'];
            $data['dynamic_content_hidden'] = $dynamic_content['hidden'];
        } else {
            $data['dynamic_content_default'] = 'enabled';
            $data['dynamic_content_hidden'] = false;
        }

        return [true, 'Successfully loaded field settings', $data];
    }

    /**
     * Update required field settings.
     *
     * @param data
     */
    public function required_fields($data)
    {

    // handle dynamic content settings first
        $dynamic_content = [];
        $dynamic_content['default'] = $data['dynamic_content_default'];
        $dynamic_content['hidden'] = (bool) $data['dynamic_content_hidden'];
        unset($data['dynamic_content_default']);
        unset($data['dynamic_content_hidden']);
        $this->db->where('name', 'dynamic_content_field');
        $this->db->delete('settings');
        $this->db->insert('settings', [
        'name'  => 'dynamic_content_field',
        'value' => json_encode($dynamic_content)]);

        // handle core metadata
        $this->db->where('name', 'core_metadata');
        $this->db->delete('settings');
        $this->db->insert('settings', [
        'name'  => 'core_metadata',
        'value' => json_encode($data)]);

        return [true, 'Successfully updated field settings.'];
    }

    /**
     * Search tags in a metadata field of the tag type. Maximum 25 tags returned
     * by query.
     *
     * @param data
     *
     * @return results
     */
    public function tag_search($data)
    {
        $results = [];

        $data['search'] = trim($data['search']);
        $data['id'] = trim($data['id']);

        $this->db->where('id', $data['id'] ?? 0);
        $this->db->where('type', 'tags');
        $this->db->what('settings');
        $tag = $this->db->get_one('media_metadata');

        if ($tag) {
            $settings = json_decode($tag['settings'], true);

            foreach ($settings['suggestions'] as $suggestion) {
                if ($data['search'] === '' || stripos($suggestion, $data['search']) !== false) {
                    $results[] = $suggestion;
                }
            }
        }

        // query is weird without search ("%%") but fine.
        $this->db->query('SELECT DISTINCT(tag) AS `tag` FROM `media_tags` WHERE `media_metadata_column_id`="' . $this->db->escape($data['id']) . '" AND `tag` LIKE "%' . $this->db->escape($data['search']) . '%"');
        $rows = $this->db->assoc_list();

        foreach ($rows as $row) {
            $results[] = $row['tag'];
        }

        // unique, sort
        $results = array_unique($results);
        sort($results);

        // return max 25 results
        return array_splice($results, 0, 25);
    }
}
