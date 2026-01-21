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
    private $model_files;
    private $controller_files;
    private $db;

    /**
     * Construct an instance of OBFLoad. It scans the models directory first, adding
     * the appropriate files to an array of models, then does the same for the
     * controllers. Finally, it goes through all the module directories, and does
     * the same for the models and controllers there.
     */
    public function __construct()
    {
        $this->db = OBFDB::get_instance();

        // generate a list of models and controllers... starting with core then modules
        $this->model_files = [];
        $this->controller_files = [];

        // find core models.
        $files = scandir('classes/models');

        foreach ($files as $file) {
            if ($file == '..' || $file == '.') {
                continue;
            }
            if (!is_file('classes/models/' . $file)) {
                continue;
            }
            if (substr($file, -4) != '.php') {
                continue;
            }
            $name_split = explode('_', $file);
            if (count($name_split) != 2) {
                continue;
            }
            $this->model_files[$name_split[0]] = 'classes/models/' . $file;
        }

        // find core controllers.
        $files = scandir('classes/controllers');

        foreach ($files as $file) {
            if ($file == '..' || $file == '.') {
                continue;
            }
            if (!is_file('classes/controllers/' . $file)) {
                continue;
            }
            if (substr($file, -4) != '.php') {
                continue;
            }
            $this->controller_files[substr($file, 0, -4)] = 'classes/controllers/' . $file;
        }

        // scan through modules.
        $modules = $this->db->get('modules') ?: [];

        foreach ($modules as $module_row) {
        // get dir, make sure dir exists.
            $dir = $module_row['directory'];
            if (!is_dir('modules/' . $dir)) {
                continue;
            }

            // get module models.
            if (is_dir('modules/' . $dir . '/models')) {
            // find module models (can override core models)
                $files = scandir('modules/' . $dir . '/models');

                foreach ($files as $file) {
                    if ($file == '..' || $file == '.') {
                        continue;
                    }
                    if (!is_file('modules/' . $dir . '/models/' . $file)) {
                        continue;
                    }
                    if (substr($file, -4) != '.php') {
                        continue;
                    }
                    $name_split = explode('_', $file);
                    if (count($name_split) != 2) {
                        continue;
                    }
                    $this->model_files[$name_split[0]] = 'modules/' . $dir . '/models/' . $file;
                }
            }

            // get module controllers
            if (is_dir('modules/' . $dir . '/controllers')) {
            // find module controllers (can override core controllers)
                $files = scandir('modules/' . $dir . '/controllers');

                foreach ($files as $file) {
                    if ($file == '..' || $file == '.') {
                        continue;
                    }
                    if (!is_file('modules/' . $dir . '/controllers/' . $file)) {
                        continue;
                    }
                    if (substr($file, -4) != '.php') {
                        continue;
                    }
                    $this->controller_files[substr($file, 0, -4)] = 'modules/' . $dir . '/controllers/' . $file;
                }
            }

            if (is_file('modules/' . $dir . '/module.php')) {
                require_once('modules/' . $dir . '/module.php');
                $module_class_name = $dir . 'Module';

                // remove underscores in name if we need to.  if it still doesn't exist, then that's a problem.
                if (!class_exists($module_class_name)) {
                    $module_class_name = str_replace('_', '', $module_class_name);
                }

                $module_instance = new $module_class_name();
                $module_instance->callbacks();
            }
        }

        // Use autoloading to include controller and model files.
        spl_autoload_register(function ($className) {
            $namespaceMap = [
                'OpenBroadcaster\\Models\\' => __DIR__ . '/../models/',
                'OpenBroadcaster\\Controllers\\' => __DIR__ . '/../controllers/',
            ];

            // TODO: scan through module directories and add them to namespace map.

            foreach ($namespaceMap as $namespacePrefix => $baseDir) {
                if (strpos($className, $namespacePrefix) === 0) {
                    $relativeClass = substr($className, strlen($namespacePrefix));

                    // Convert further namespace separators to directory separators.
                    $file = $baseDir . str_replace('\\', '/', $relativeClass);

                    // Check if model, which has a weird naming scheme (possible TODO, currently breaks
                    // too many things).
                    if (str_ends_with($file, 'Model')) {
                        $file = substr($file, 0, -5) . '_model';
                    }

                    // Add extension.
                    $file = $file . '.php';

                    if (file_exists($file)) {
                        require_once $file;
                        return;
                    }
                }
            }
        });
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
     *
     * @return model_instance
     */
    public function model($model)
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $model)) {
            return false;
        }
        if (!isset($this->model_files[strtolower($model)])) {
            return false;
        }
        $model_file = $this->model_files[strtolower($model)];

        if (strpos($model_file, 'modules/') === 0) {
            // TODO: Module class namespacing, then no longer needs to require file.
            $model_class = '\\' . $model . 'Model';
            require_once($model_file);
        } else {
            $model_class = 'OpenBroadcaster\\Models\\' . $model . 'Model';
        }
        return new $model_class();
    }

    /**
     * Load a controller and return the instance. Used primarily by the API to
     * access its methods.
     *
     * @param controller
     *
     * @return controller_instance
     */
    public function controller($controller)
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $controller)) {
            return false;
        }
        if (!isset($this->controller_files[strtolower($controller)])) {
            return false;
        }
        $controller_file = $this->controller_files[strtolower($controller)];

        if (strpos($controller_file, 'modules/') === 0) {
            // TODO: Module class namespacing, then no longer needs to require file.
            $controller_class = '\\' . $controller;
            require_once($controller_file);
        } else {
            $controller_class = 'OpenBroadcaster\\Controllers\\' . $controller;
        }
        return new $controller_class();
    }
}
