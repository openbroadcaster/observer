<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
        $this->html_data = [];
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
        return [true,'Themes',$this->models->ui('get_themes')];
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
        return [true, 'Languages', $this->models->ui('get_languages')];
    }

    /**
     * Return translation strings for the user's language.
     *
     * @return strings
     *
     * @route GET /v2/ui/strings
     */
    public function strings()
    {
        $strings = $this->models->ui('strings');

        return [true, 'Translation strings', $strings];
    }

    /**
     * TTS output in OGG format, base64 encoded.
     *
     * @param text Text to convert into speech.
     * @return ogg_audio
     *
     * @route GET /v2/ui/tts
     */
    public function tts()
    {
        $text = $this->data('text');

        if (! $text) {
            die();
        }

        $festival = [
        ['pipe','r'],
        ['pipe','w'],
        ['file','/dev/null','a']
        ];

        $process = proc_open('text2wave | oggenc - -o -', $festival, $pipes);

        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        $output = base64_encode(stream_get_contents($pipes[1]));
        fclose($pipes[1]);

        return [true, 'OGG data (base64 encoded)', $output];
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

        $this->html_data = [];
        $this->find_core_html_files($this->theme);
        foreach ($modules as $module) {
            $this->find_module_html_files('modules/' . $module['dir'] . '/html');
        }

        return [true,'HTML Data',$this->html_data];
    }

    // TODO this should be in UI model? then we don't need to check theme in this file?
    private function find_core_html_files($theme = false, $dir = '')
    {
        $files = scandir('public/html/' . $dir);

        foreach ($files as $file) {
            $dirfile = ($dir != '' ? $dir . '/' : '') . $file;
            $fullpath = 'public/html/' . $dirfile;

            if (is_dir($fullpath) && $file[0] != '.') {
                $this->find_core_html_files($theme, $dirfile);
            } elseif (is_file($fullpath) && substr($fullpath, -5) == '.html') {
                // use theme override?
                if ($theme && is_file('public/themes/' . $theme . '/' . $fullpath)) {
                    $fullpath = 'public/themes/' . $theme . '/' . $fullpath;
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
