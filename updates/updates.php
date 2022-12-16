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

require(__DIR__ . '/checker.php');

class OBUpdate
{
    public function __construct()
    {
        $this->error = false;
        $this->db = new OBFDB();
        $this->load = OBFLoad::get_instance();
        $this->models = OBFModels::get_instance();
    }
}

class OBFUpdates
{
    public function __construct()
    {
        $checker = new OBFChecker();
        $checker_methods = get_class_methods('OBFChecker');
        $this->checker_results = array();

        foreach ($checker_methods as $checker_method) {
            $result = $checker->$checker_method();
            $this->checker_results[] = $result;
            if ($result[2] == 2) {
                $this->checker_status = false;
                return;
            } // fatal error encountered.
        }

        $this->dbver = $checker->dbver;
        $this->checker_status = true;

        $this->db = new OBFDB();
        $this->auth();
    }

    public function auth()
    {
        // CLI doesn't require auth.
        if (php_sapi_name() === 'cli') {
            return;
        }

        // no user or password set for updates.
        if (!defined('OB_UPDATES_USER') || !defined('OB_UPDATES_PW')) {
            die('Please set OB_UPDATES_USER and OB_UPDATES_PW in config.php. Administrator login is no longer supported for OB updates since July 2020.');
        }

        // use http auth.
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != OB_UPDATES_USER || !password_verify($_SERVER['PHP_AUTH_PW'], OB_UPDATES_PW)) {
            header('WWW-Authenticate: Basic realm="OpenBroadcaster Updates"');
            header('HTTP/1.0 401 Unauthorized');
            die();
        }
    }

    // get an array of update classes.
    public function updates()
    {
        $scandir = scandir('./updates', SCANDIR_SORT_ASCENDING);
        $updates = array();
        foreach ($scandir as $file) {
            if (!preg_match('/^[0-9]{8}\.php$/', $file)) {
                continue;
            }
            $file_explode = explode('.', $file);
            $version = $file_explode[0];

            require($version . '.php');

            $class_name = 'OBUpdate' . $version;
            $update_class = new $class_name();
            $update_class->needed = $version > $this->dbver;
            $update_class->version = $version;

            $updates[] = $update_class;
        }

        return $updates;
    }

    // run the update
    public function run($update)
    {
        $result = $update->run();

        // if update was successful, update our database version number.
        if ($result) {
            $this->db->where('name', 'dbver');
            $this->db->update('settings', array('value' => $update->version));
        }

        return $result;
    }
}

$u = new OBFUpdates();
