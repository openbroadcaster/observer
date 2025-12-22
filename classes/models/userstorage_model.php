<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Store name/value pairs users.
 *
 * @package Model
 */
class UserStorageModel extends OBFModel
{
    /**
     * Insert or update a name/value pair.
     *
     * @param data
     *
     * @return id
     */
    public function save($args)
    {
        if (!preg_match('/^[a-z0-9-]{1,255}$/i', $args['name'])) {
            return [false, 'Invalid key (allowed characters alphanumeric plus hyphen).'];
        }

        if ($this('get', $args)[0]) {
            $this->db->where('user_id', $args['user_id']);
            $this->db->where('name', $args['name']);
            $this->db->update('users_storage', [
              'value' => json_encode($args['value'])
            ]);
        } else {
            $data = [
              'user_id' => $args['user_id'],
              'name'    => $args['name'],
              'value'   => json_encode($args['value'])
            ];
            $this->db->insert('users_storage', $data);
        }

        return [true, 'Saved.'];
    }

    /**
     * Get user storage data.
     *
     * @param data
     *
     * @return string value
     */
    public function get($args)
    {
        $this->db->where('user_id', $args['user_id']);
        $this->db->where('name', $args['name']);
        $value = $this->db->get('users_storage');

        if (!$value) {
            return [false, 'Not found.'];
        } else {
            return [true, 'Success.', json_decode($value[0]['value'])];
        }
    }

    /**
     * Get all user storage data.
     *
     * @param data  Contains user id
     *
     * @return array values
     */
    public function get_all($args)
    {
        $this->db->where('user_id', $args['user_id']);
        $data = $this->db->get('users_storage');

        $values = [];
        foreach ($data as $value) {
            $values[$value['name']] = json_decode($value['value']);
        }

        return [true, 'Success.', $values];
    }
}
