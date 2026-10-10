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
    public function __construct()
    {
        parent::__construct();
        $this->user->require_permission('import_sample_data');
        $this->SampleDataModel = $this->load->model('SampleData', 'SampleData');
    }

    /**
     * @route GET /profiles
     */
    public function listProfiles()
    {
        return [true, 'Sample data profiles.', $this->SampleDataModel('listProfiles')];
    }

    /**
     * The envelope is always success=true at the API level; the real outcome
     * (success/log/error) is in the data payload so OB.API.request (which
     * returns only data) can read it.
     *
     * @route POST /run
     */
    public function runProfile()
    {
        $profile = trim((string) $this->data('profile'));

        // Fallback while MODULES_5.5.md "v2 API integration for JS with modules"
        // TODO is open: under X-Auth session auth, api.php skips the JSON body
        // parse, so $this->data() returns nothing. Read php://input ourselves.
        if ($profile === '') {
            $body = json_decode(file_get_contents('php://input'), true);
            $profile = is_array($body) ? trim((string) ($body['profile'] ?? '')) : '';
        }

        if ($profile === '' || !preg_match('/^[a-z0-9_]+$/', $profile)) {
            return [true, 'Invalid profile name.', ['success' => false, 'log' => [], 'error' => 'Invalid profile name.']];
        }

        return [true, 'Sample data import attempted.', $this->SampleDataModel('runProfile', $profile)];
    }
}
