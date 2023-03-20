<?php

/*
    Copyright 2012-2023 OpenBroadcaster, Inc.

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
     * Get logs between two timestamps.
     *
     * @param start
     * @param end
     *
     * @return log
     *
     * @route GET /v2/playlog/(:start:)/(:end:)
     * @route GET /v2/playlog/(:start:)
     */
    public function get()
    {
        $result = $this->models->playlog('get', $this->data('start'), $this->data('end'));
        return [true, 'Playlog.', $result];
    }
}
