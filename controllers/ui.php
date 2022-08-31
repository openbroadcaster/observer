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
 * UI manager handles such things as themes, languages, and outputting the correct
 * HTML in general.
 *
 * @package Controller
 */
class UI extends OBFController
{
    public function __construct()
    {
        parent::__construct();
        $this->user->require_authenticated();
        $this->html_data = array();
        $this->theme = !empty($this->user->userdata['theme']) && $this->user->userdata['theme'] != 'default' ? $this->user->userdata['theme'] : false;
    }

    /**
     * List all UI themes.
     *
     * @return themes
     *
     * @route GET /v2/ui/themes
     */
    public function get_themes()
    {
        return array(true,'Themes',$this->models->ui('get_themes'));
    }

    /**
     * List all languages
     *
     * @return languages
     *
     * @route GET /v2/ui/languages
     */
    public function get_languages()
    {
        return array(true, 'Languages', $this->models->ui('get_languages'));
    }

    /**
   * Returns all HTML files in the framework as a single JSON object, including
   * the views for all installed modules.
   *
   * @return [html_file => html]
   *
   * @route GET /v2/ui/html
   */
    public function html()
    {
        $modules = $this->models->modules('get_installed');

        $this->html_data = array();
        $this->find_core_html_files($this->theme);
        foreach ($modules as $module) {
            $this->find_module_html_files('modules/' . $module['dir'] . '/html');
        }

        return array(true,'HTML Data',$this->html_data);
    }

    // TODO this should be in UI model? then we don't need to check theme in this file?
    private function find_core_html_files($theme = false, $dir = '')
    {
        $files = scandir('html/' . $dir);

        foreach ($files as $file) {
            $dirfile = ($dir != '' ? $dir . '/' : '') . $file;
            $fullpath = 'html/' . $dirfile;

            if (is_dir($fullpath) && $file[0] != '.') {
                $this->find_core_html_files($theme, $dirfile);
            } elseif (is_file($fullpath) && substr($fullpath, -5) == '.html') {
                // use theme override?
                if ($theme && is_file('themes/' . $theme . '/' . $fullpath)) {
                    $fullpath = 'themes/' . $theme . '/' . $fullpath;
                }
                // echo "OB.UI.htmlCache['$dirfile'] = $.ajax({'url': '$fullpath', 'async': false}).responseText;\n";
                $this->html_data[$dirfile] = file_get_contents($fullpath);
            }
        }
    }

    // TODO this should be in UI model? then we don't need to check theme in this file?
    private function find_module_html_files($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);

        foreach ($files as $file) {
            $dirfile = $dir . '/' . $file;

            if (is_dir($dirfile) && $file[0] != '.') {
                $this->find_module_html_files($dirfile);
            } elseif (is_file($dirfile)) {
                $index_array = explode('/', $dirfile);
                array_splice($index_array, 2, 1);
                // echo "OB.UI.htmlCache['".implode('/',$index_array)."'] = $.ajax({'url': '$dirfile', 'async': false}).responseText;\n";
                $this->html_data[implode('/', $index_array)] = file_get_contents($dirfile);
            }
        }
    }
}
