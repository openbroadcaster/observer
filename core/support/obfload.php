<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Loading class. Manages the loading of OpenBroadcaster files, models, and
 * controllers.
 *
 * @package Class
 */
class OBFLoad
{
    private $db;

    /**
     * Construct an instance of OBFLoad. It creates an instance of every installed top-level
     * module using the definition file in each module folder.
     */
    public function __construct()
    {
        $this->db = OBFDB::get_instance();

        // scan through modules.
        $modules = $this->db->get('modules') ?: [];

        foreach ($modules as $module_row) {
        // get dir, make sure dir exists.
            $dir = $module_row['directory'];
            if (!is_dir(OB_LOCAL . '/modules/' . $dir)) {
                continue;
            }

            $module_class = 'OpenBroadcaster\\Modules\\' . $dir . '\\' . $dir;
            $module_instance = new $module_class();
            $module_instance->callbacks();
        }
    }


    /**
     * Create an instance of OBFLoad or return the already-created instance.
     *
     * @return instance
     */
    public static function &get_instance()
    {
        static $instance;

        if (isset($instance)) {
            return $instance;
        }

        $instance = new OBFLoad();

        return $instance;
    }

    /**
     * Load a model and return the instance. Used primarly by controllers to
     * access associated data.
     *
     * @param model
     * @param module Optional, specify module name
     *
     * @return model_instance
     */
    public function model($model, $module = null)
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $model)) {
            return false;
        }
        $model_file = $this->model_files[strtolower($model)] ?? null;

        if ($module !== null) {
            $model_class = 'OpenBroadcaster\\Modules\\' . $module . '\\Models\\' . $model;
        } else {
            $model_class = 'OpenBroadcaster\\Models\\' . $model;
        }

        return new $model_class();
    }

    /**
     * Load a controller and return the instance. Used primarily by the API to
     * access its methods.
     *
     * @param controller
     * @param module Optional, specify module name
     *
     * @return controller_instance
     */
    public function controller($controller, $module = null)
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $controller)) {
            return false;
        }

        if ($module !== null) {
            $controller_class = 'OpenBroadcaster\\Modules\\' . $module . '\\Controllers\\' . $controller;
        } else {
            $controller_class = 'OpenBroadcaster\\Controllers\\' . $controller;
        }
        return new $controller_class();
    }
}
