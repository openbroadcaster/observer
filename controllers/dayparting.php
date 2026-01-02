<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Manages dynamic selection media restrictions based on media search criteria, date, and time.
 * Requires 'manage media settings' permission.
 *
 * @package Controller
 */
class Dayparting extends OBFController
{
    public function __construct()
    {
        parent::__construct();
        $this->user->require_permission('manage_media_settings');
    }

    /**
     * Get all restrictions.
     *
     * @return restrictions
     *
     * @route GET /v2/dayparting/search
     */
    public function search()
    {
        return $this->models->dayparting('search');
    }

    /**
     * Get restriction by id.
     *
     * @param id
     *
     * @return restrction
     *
     * @route GET /v2/dayparting/(:id:)
     */
    public function get()
    {
        return $this->models->dayparting('get', ['id' => $this->data('id')]);
    }

    /**
     * Save restriction.
     *
     * @param id
     * @param start_month
     * @param start_day
     * @param start_time
     * @param end_month
     * @param end_day
     * @param end_time
     * @param filters
     *
     * @return id
     *
     * @route PUT /v2/dayparting/(:id:)
     */
    public function save()
    {
        $data = [];
        $data['id'] = $this->data('id');
        $data['description'] = $this->data('description');
        $data['type'] = $this->data('type');
        $data['start'] = $this->data('start');
        $data['end'] = $this->data('end');
        $data['dow'] = $this->data('dow');
        $data['filters'] = $this->data('filters');

        return $this->models->dayparting('save', $data);
    }

    /**
     * Delete restriction.
     *
     * @param id
     *
     * @route DELETE /v2/dayparting/(:id:)
     */
    public function delete()
    {
        return $this->models->dayparting('delete', ['id' => $this->data('id')]);
    }
}
