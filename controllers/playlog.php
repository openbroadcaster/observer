<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Playlog controller allows for creation (TODO: part of remote.php at this point) and
 * retrieval of player log data.
 *
 * @package Controller
 */
class Playlog extends OBFController
{
    public function __construct()
    {
        parent::__construct();

        $this->user->require_authenticated();
    }

     /**
     * Get logs between two timestamps for a player.
     *
     * @param id The player ID
     * @param start
     * @param end
     *
     * @return log
     *
     * @route GET /v2/playlog/(:id:)/(:start:)/(:end:)
     * @route GET /v2/playlog/(:id:)/(:start:)
     */
    public function get()
    {
        $id    = $this->data('id');
        $start = $this->data('start');
        $end   = $this->data('end');

        $result = $this->models->playlog('get', $id, $start, $end);
        return [true, 'Playlog.', $result];
    }
}
