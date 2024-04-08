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
 * Manages the UI, finding core and theme image/CSS/JS files, user languages,
 * and translation strings.
 *
 * @package Model
 */
class UIModel extends OBFModel
{
    public function __construct()
    {
        parent::__construct();
        $this->modules = $this->models->modules('get_installed');
        $this->theme = !empty($this->user->userdata['theme']) && $this->user->userdata['theme'] != 'default' ? $this->user->userdata['theme'] : false;
    }

    /**
     * Get core, theme, and enabled-module image files.
     *
     * @return image_files
     */
    public function image_files($args = [])
    {
        $image_files = $this->find_files('images');
        if ($this->theme) {
            $image_files = array_merge($image_files, $this->find_files('themes/' . $this->theme . '/images'));
        } // add our custom theme images.
        foreach ($this->modules as $module) {
            $image_files = array_merge($image_files, $this->find_files('modules/' . $module['dir'] . '/images'));
        }
        return $image_files;
    }

    /**
     * Get core, theme, and enabled-module CSS files.
     *
     * @return css_files
     */
    public function css_files($args = [])
    {
        if ($this->theme && file_exists('themes/' . $this->theme . '/style.css')) {
            $css_files[] = 'themes/' . $this->theme . '/style.css';
        } else {
            $css_files[] = 'themes/default/style.css';
        }

        if ($this->theme) {
            $css_files = array_merge($css_files, $this->find_files('themes/' . $this->theme . '/css_theme', 'css'));
        }
        foreach ($this->modules as $module) {
            $css_files = array_merge($css_files, $this->find_files('modules/' . $module['dir'] . '/css', 'css'));
        }
        return $css_files;
    }

    /**
     * Get core and enabled-module JS files.
     *
     * @return js_files
     */
    public function js_files($args = [])
    {
        $js_files = $this->find_files('js', 'js');
        foreach ($this->modules as $module) {
            $js_files = array_merge($js_files, $this->find_files('modules/' . $module['dir'] . '/js', 'js'));
        }

        return $js_files;
    }

    /**
     * Get all available themes.
     *
     * @return themes
     */
    public function get_themes($args = [])
    {
        $dirs = scandir('themes');

        $themes = [];

        foreach ($dirs as $dir) {
            if ($dir[0] == '.' && !is_dir($dir)) {
                continue;
            }
            if (!file_exists('themes/' . $dir . '/style.css')) {
                continue;
            }

            $css = file_get_contents('themes/' . $dir . '/style.css');
            $comment = [];

            // https://www.w3.org/TR/CSS2/grammar.html#scanner
            if (!preg_match('#\/\*[^*]*\*+([^/*][^*]*\*+)*\/#', $css, $comment)) {
                continue;
            }

            // remove /*, */
            $comment = trim(preg_replace(['#^/\*#','#\*/$#'], '', trim($comment[0])));

            // get theme data
            $data = [];
            $rows = explode("\n", $comment);
            foreach ($rows as $row) {
                $tmp = explode(':', $row);
                $key = strtolower(trim(array_shift($tmp)));
                $value = trim(implode(':', $tmp));
                $data[$key] = $value;
            }

            // only using description for now
            if (isset($data['description']) && $data['description'] !== '') {
                $themes[$dir] = $data['description'];
            }
        }

        // sort by description
        asort($themes);

        return $themes;
    }

    /**
     * Get all available languages.
     *
     * @return languages
     */
    public function get_languages($args = [])
    {
        $languages = [];

        foreach ($this->db->get('translations_languages') as $language) {
            $languages[] = array(
            'name' => $language['name'],
            'code' => $language['code']
            );
        }

        return $languages;
    }

    /**
     * Get the active language set for the current user. Returns FALSE if no
     * language is set or no user is logged in.
     *
     * @return language
     */
    public function get_user_language($args = [])
    {
        $all_languages = $this->get_languages();

        // no language set?
        if (!$this->user->userdata || empty($this->user->userdata['language'])) {
            return false;
        }

        // loop and return language if code match
        foreach ($all_languages as $language) {
            if ($language['code'] == $this->user->userdata['language']) {
                return $language;
            }
        }
        return false;
    }

    /**
     * Get translation strings for current user's active language. Returns an
     * empty array if no strings are found or no user is logged in.
     *
     * @return strings
     */
    public function strings($args = [])
    {

    // no language set?
        if (!$this->user->userdata || empty($this->user->userdata['language'])) {
            return [];
        }

        // loop and return language if code match
        $all_languages = $this->get_languages();
        foreach ($all_languages as $language) {
            if ($language['code'] == $this->user->userdata['language']) {
                $strings = [];

                $this->db->where('code', $language['code']);
                $lang_id = $this->db->get_one('translations_languages')['id'];

                $this->db->where('language_id', $lang_id);
                $translations = $this->db->get('translations_values');

                foreach ($translations as $translation) {
                    $strings[$translation['source_str']] = $translation['result_str'];
                }

                return $strings;
            }
        }

        // language not found
        return [];
    }

    /**
     * Recursive function to find files in a specific directory. If no valid
     * directory is provided, the reference array will be returned unchanged (or
     * an empty array if none is provided).
     *
     * @param dir Directory to recursively find files in.
     * @param ext File extension to filter for.
     * @param array Reference to array to update with results. Results will be added to already existing array.
     */
    private function find_files($dir, $ext = false, &$array = [])
    {
        if (!is_dir($dir)) {
            return $array;
        }

        $files = scandir($dir);

        foreach ($files as $file) {
            $dirfile = $dir . '/' . $file;

            // scan if directory
            if (is_dir($dirfile) && $file[0] != '.') {
                $this->find_files($dirfile, $ext, $array);
            } elseif (is_file($dirfile)) {
                // or add file if file
                $array[] = $dirfile;
            }
        }

        return $array;
    }
}
