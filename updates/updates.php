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
    public function __construct($module = null)
    {
        $this->module = $module;

        $checker = new OBFChecker($module);
        $checker_methods = get_class_methods('OBFChecker');
        $checker_methods = array_filter($checker_methods, fn($x) => $x !== '__construct');
        $this->checker_results = array();

        foreach ($checker_methods as $checker_method) {
            if ($checker_method == 'directories_valid' && php_sapi_name() == 'cli') {
                // skip directories valid check for CLI, this needs to be run via web.
                continue;
            }

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
        if ($this->module === null) {
            $scandir = scandir('./updates', SCANDIR_SORT_ASCENDING);
        } else {
            $dir = "./modules/{$this->module}/updates/";
            if (file_exists($dir)) {
                $scandir = scandir($dir, SCANDIR_SORT_ASCENDING);
            } else {
                $scandir = [];
            }
        }

        $updates = array();
        foreach ($scandir as $file) {
            if (!preg_match('/^[0-9]{8}\.php$/', $file)) {
                continue;
            }
            $file_explode = explode('.', $file);
            $version = $file_explode[0];

            if ($this->module === null) {
                require($version . '.php');
            } else {
                require("./modules/{$this->module}/updates/{$version}.php");
            }

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
            if ($this->module === null) {
                $this->db->where('name', 'dbver');
            } else {
                $this->db->where('name', 'dbver-' . $this->module);
            }
            $this->db->update('settings', array('value' => $update->version));
        }

        return $result;
    }
}

$u = new OBFUpdates();
