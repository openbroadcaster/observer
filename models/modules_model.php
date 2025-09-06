<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Module management.
 *
 * @package Model
 */
class ModulesModel extends OBFModel
{
    /**
     * Get all installed modules.
     *
     * @param object Return as module object or information array. Default FALSE.
     *
     * @return modules
     */
    public function get_installed($object = false)
    {
        return $this('get_all', true, $object);
    }

    /**
     * Get all available modules.
     *
     * @param object Return as module object or information array. Default FALSE.
     *
     * @return modules
     */
    public function get_not_installed($object = false)
    {
        return $this('get_all', false, $object);
    }

    /**
     * Get all modules of a certain type.
     *
     * @param installed Installed (TRUE) or available (FALSE) modules. Default TRUE.
     * @param object Return as module object or information array. Default FALSE.
     *
     * @return modules
     */
    public function get_all($installed = true, $object = false)
    {
        // TODO ignore any module name "core" as this is a reserved name used as a prefix for core functionality.
        $modules = scandir('modules');

        $modules_list = [];

        $installed_rows = $this->db->get('modules');
        $installed_modules = [];

        foreach ($installed_rows as $row) {
            $installed_modules[] = $row['directory'];
        }

        foreach ($modules as $module) {
            if ($module == '..' || $module == '.' || !is_dir('modules/' . $module)) {
                continue;
            }

            if (is_file('modules/' . $module . '/module.php')) {
                require_once('modules/' . $module . '/module.php');
                $module_class_name = $module . 'Module';

                // remove underscores in name if we need to.
                if (!class_exists($module_class_name)) {
                    $module_class_name = str_replace('_', '', $module_class_name);
                }

                $module_instance = new $module_class_name();

                if (($installed && array_search($module, $installed_modules) !== false) || (!$installed && array_search($module, $installed_modules) === false)) {
                    $modules_list[$module] = ($object ? $module_instance : ['name' => $module_instance->name, 'description' => $module_instance->description, 'dir' => $module]);
                }
            }
        }

        return $modules_list;
    }

    /**
     * Install a module.
     *
     * @param module_name
     *
     * @return status
     */
    public function install($module_name)
    {
        $module_list = $this('get_all', false, true);

        // module not found?
        if (!isset($module_list[$module_name])) {
            return false;
        }

        $module = $module_list[$module_name];

        // install the module as per the modules instructions
        $install = $module->install();
        if (!$install) {
            return false;
        }

        // add the module to our installed module list.
        $this->db->insert('modules', ['directory' => $module_name]);

        return true;
    }

    /**
     * Uninstall a module.
     *
     * @param module_name
     *
     * @return status
     */
    public function uninstall($module_name)
    {
        $module_list = $this('get_all', true, true);

        // module not found?
        if (!isset($module_list[$module_name])) {
            return false;
        }

        $module = $module_list[$module_name];

        // install the module as per the modules instructions
        $uninstall = $module->uninstall();
        if (!$uninstall) {
            return false;
        }

        // remove module from installed module list in db
        $this->db->where('directory', $module_name);
        $this->db->delete('modules');

        return true;
    }

    /**
     * Purge the data from a module. Note that this method will first attempt to
     * uninstall the module.
     *
     * @param module_name
     *
     * @return status
     */
    public function purge($module_name)
    {
        $modulesAvailable = $this->get_all(false, true);
        $modulesInstalled = $this->get_all(true, true);

        // Check if module exists in either available or installed modules.
        if (isset($modulesAvailable[$module_name])) {
            $module = $modulesAvailable[$module_name];
        } elseif (isset($modulesInstalled[$module_name])) {
            $module = $modulesInstalled[$module_name];

            $uninstall = $module->uninstall();
            if (! $uninstall) {
                return false;
            }

            // Remove module from installed module list in db.
            $this->db->where('directory', $module_name);
            $this->db->delete('modules');
        } else {
            return false;
        }

        // Remove dbver for module from settings if it exists, to ensure updates
        // are re-run on next install.
        $this->db->where('name', 'dbver-' . $module_name);
        $this->db->delete('settings');

        // Purge the module as per the modules instructions.
        $purge = $module->purge();
        if (! $purge) {
            return false;
        }

        return true;
    }
}
