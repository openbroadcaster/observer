<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

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
 * Allow clients (like the main Javascript/web client, or a third party client)
 * to store data associated with a user. DEPRECATED.
 *
 * @package Controller
 */
class ClientStorage extends OBFController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Store client information. Takes the name of the client and some data, then
     * stores that in the database. Requires the user to be authenticated, as well
     * as a specific permission if global data is being stored.
     *
     * @param client_name The name of the client requesting to store data.
     * @param data The data being stored.
     * @param global A boolean set to TRUE if trying to store global data. Requires the manage_global_client_storage permission.
     *
     * @route PUT /clientstorage
     */
    public function store()
    {
        $this->user->require_authenticated();

        $data['client_name'] = $this->data('client_name');
        $data['data'] = $this->data('data');

        // global data or user data?  global data is for all users.
        if ($this->data('global')) {
            $data['user_id'] = 0;
            $this->user->require_permission('manage_global_client_storage'); // we need special permission to store globally. (but not to get globally)
        } else {
            $data['user_id'] = $this->user->param('id');
        }

        $validation = $this->models->clientstorage('validate', $data);
        if ($validation[0]==false) {
            return $validation;
        }

        $this->models->clientstorage('store', $data);

        return array(true,'Data has been stored.');
    }

    /**
    * Retrieve client information. Requires authentication if retrieving non-global
    * authentication.
    *
    * @param client_name The name of the client requesting to retrieve data.
    * @param global A boolean set to TRUE if trying to retrieve global data. Does NOT require authentication in this case.
    *
    * @return storage_data_array
    *
    * @route GET /clientstorage/(:client_name:)/(:global:)
    */
    public function get()
    {
        $client_name = $this->data('client_name');
        $global = $this->data('global');

        // global data or user data?  global is for all users.
        if ($global) {
            $user_id = 0;
        } else {
            $user_id = $this->user->param('id');
            $this->user->require_authenticated(); // require authenticated if it's user-dependent.
        }

        return $this->models->clientstorage('get', ['client_name' => $client_name, 'user_id' => $user_id]);
    }
}
