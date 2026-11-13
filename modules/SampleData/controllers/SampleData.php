<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Sample data import module.
 *
 * @package Controller.SampleData
 */

namespace OpenBroadcaster\Modules\SampleData\Controllers;

use OpenBroadcaster\Base\Controller;

class SampleData extends Controller
{
    protected $SampleDataModel;

    public function __construct()
    {
        parent::__construct();
        $this->user->require_permission('import_sample_data');
        $this->SampleDataModel = $this->load->model('SampleData', 'SampleData');
    }

    /**
     * Get the sample data profiles.
     *
     * @return profiles
     *
     * @route GET /profiles
     */
    public function profiles()
    {
        return [true, 'Sample data profiles.', $this->SampleDataModel('list_profiles')];
    }

    /**
     * Check a profile before importing it.
     *
     * @param profile
     *
     * @return [problem, fresh, existing, download_mb, steps]
     *
     * @route GET /preflight
     */
    public function preflight()
    {
        return [true, 'Preflight.', $this->SampleDataModel('preflight', (string) $this->input('profile'))];
    }

    /**
     * Run one step of an import.
     *
     * @param profile
     * @param step
     * @param offset Where the import step carries on from.
     * @param confirmed An install with its own content has to confirm first.
     *
     * @return [error, log, next_offset, details]
     *
     * @route POST /step
     */
    public function step()
    {
        return $this->SampleDataModel(
            'run_step',
            (string) $this->input('profile'),
            (string) $this->input('step'),
            (int) $this->input('offset'),
            (bool) $this->input('confirmed')
        );
    }

    // api.php only parses v2 request data with an Authorization header, and the web UI
    // authenticates with X-Auth headers, so $this->data() is empty for it.
    private function input($name)
    {
        $value = $this->data($name);
        if ($value !== false) {
            return $value;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return $_GET[$name] ?? null;
        }

        $body = json_decode(file_get_contents('php://input'), true);

        return $body[$name] ?? null;
    }
}
