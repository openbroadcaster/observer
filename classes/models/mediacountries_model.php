<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Secondary model for managing media countries.
 *
 * @package Model
 */
class MediaCountriesModel extends OBFModel
{
    /**
     * Get all countries from the database.
     *
     * @return countries
     */
    public function get_all()
    {
        $this->db->orderby('name');
        $types = $this->db->get('countries');

        return $types;
    }

    /**
     * Get country by ID.
     *
     * @param id
     *
     * @return country
     */
    public function get_by_id($id)
    {
        $this->db->where('country_id', $id);
        $country = $this->db->get('countries');

        return $country;
    }
}
