<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

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

        // add the module to our installed module list.
        $this->db->where('directory', $module_name);
        $this->db->delete('modules');

        return true;
    }
}
