<?php

/*
    Copyright 2012-2022 OpenBroadcaster, Inc.

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

// verify the OB installation. each method will be run, and should return array(NAME, DESCRIPTION (string or array), STATUS=success (0) / warning (1) / error (2) ).
// if error is returned, subsequent methods will not be run.
class OBFChecker
{
    public function php_version()
    {
        if (version_compare(phpversion(), '5.4', '<')) {
            return array('PHP Version','PHP v5.4 or higher is required (v' . phpversion() . ' detected).',2);
        }
        return array('PHP Version','PHP v' . phpversion() . ' detected.',0);
    }

    public function php_mysql_extension()
    {
        if (!extension_loaded('mysqli')) {
            return array('PHP MySQL extension', 'PHP MySQL extension not found.',2);
        }
        return array('PHP MySQL extension', 'PHP MySQL extension detected.',0);
    }

    public function php_extensions()
    {
        $errors = array();

        if (!extension_loaded('gd')) {
            $errors[] = 'GD extension not found.';
        }
        if (!extension_loaded('curl')) {
            $errors[] = 'cURL extension not found.';
        }
        if (!extension_loaded('fileinfo')) {
            $errors[] = 'Fileinfo extension not found.';
        }

        if (!empty($errors)) {
            return array('PHP Extensions',$errors,2);
        }
        return array('PHP Extensions','Required PHP extensions found.',0);
    }

    public function php_extensions_warning()
    {
        $errors = array();

        if (!extension_loaded('imagick')) {
            $errors[] = 'ImageMagick (imagick) extension not found (SVG preview will not function).';
        }

        if (!empty($errors)) {
            return array('PHP Extensions',$errors,1);
        }
        return array('PHP Extensions','Optional PHP extensions found.',0);
    }

    public function which()
    {
        if (!exec('which which')) {
            return array('Program detection','Tool to detect dependencies (which) is not available.  Program detection will fail below.',1);
        }
        return array('Program detection','Tool to detect dependencies (which) is available.',0);
    }

    public function tts()
    {
        $oggenc = !!exec('which oggenc');
        $text2wave = !!exec('which text2wave');

        if (!$oggenc && !$text2wave) {
            return array('Text-to-Speech','Text-to-speech support requires programs oggenc and text2wave.  Install vorbis-tools and festival packages on Debian/Ubuntu.',1);
        } elseif (!$oggenc) {
            return array('Text-to-Speech','Text-to-speech support requires program oggenc.  Install vorbis-tools package on Debian/Ubuntu.',1);
        } elseif (!$text2wave) {
            return array('Text-to-Speech','Text-to-speech support requires program text2wave.  Install festival package on Debian/Ubinti.',1);
        } else {
            return array('Text-to-Speech','Required components for text-to-speech found.',0);
        }
    }

    public function avconv()
    {
        if (!exec('which avconv')) {
            return array('Audio & Video Support','Audio and video support requires program avconv. Install libav-tools package on Debian/Ubuntu.',1);
        } else {
            return array('Audio & Video Support','AVConv found.  Audio and video formats should be supported.' . "\n\n" . 'Make sure the supporting libraries are installed (libavcodec-extra-53, libavdevice-extra-53, libavfilter-extra-2, libavutil-extra-51, libpostproc-extra-52, libswscale-extra-2 or similar packages on Debian/Ubuntu).',0);
        }
    }

    public function config_file_exists()
    {
        if (!file_exists(__DIR__ . '/../config.php')) {
            return array('Settings file', 'Settings file (config.php) not found.',2);
        }
        return array('Settings file','Settings file (config.php) found.  Will try to load components.php and config.php now.' . "\n\n" . 'If you see an error below (or if output stops), check config.php for errors.',0);
    }

    public function config_file_valid()
    {
        require_once(__DIR__ . '/../components.php');

        $fatal_error = false;
        $errors = array();

        // make sure everything is defined
        if (!defined('OB_DB_USER')) {
            $errors[] = 'OB_DB_USER (database user) not set.';
        }
        if (!defined('OB_DB_PASS')) {
            $errors[] = 'OB_DB_PASS (database password) not set.';
        }
        if (!defined('OB_DB_HOST')) {
            $errors[] = 'OB_DB_HOST (database hostname) not set.';
        }
        if (!defined('OB_DB_NAME')) {
            $errors[] = 'OB_DB_NAME (database name) not set.';
        }
        if (!defined('OB_HASH_SALT')) {
            $errors[] = 'OB_HASH_SALT (password hash salt) not set.';
        }
        if (!defined('OB_MEDIA')) {
            $errors[] = 'OB_MEDIA (media directory) not set.';
        }
        if (!defined('OB_MEDIA_UPLOADS')) {
            $errors[] = 'OB_MEDIA_UPLOADS (media unapproved/uploads directory) not set.';
        }
        if (!defined('OB_MEDIA_ARCHIVE')) {
            $errors[] = 'OB_MEDIA_ARCHIVE (media archive directory) not set.';
        }
        if (!defined('OB_CACHE')) {
            $errors[] = 'OB_CACHE (cache directory) not set.';
        }
        if (!defined('OB_SITE')) {
            $errors[] = 'OB_SITE (installation web address) not set.';
        }
        if (!defined('OB_EMAIL_REPLY')) {
            $errors[] = 'OB_EMAIL_REPLY (email address used to send emails) not set.';
        }
        if (!defined('OB_EMAIL_FROM')) {
            $errors[] = 'OB_EMAIL_FROM (email name used to send emails) not set.';
        }

        // if everything is defined, validate settings.
        if (empty($errors)) {
            $connection = mysqli_connect(OB_DB_HOST, OB_DB_USER, OB_DB_PASS);
            if (!$connection) {
                $errors[] = 'Unable to connect to database (check database settings).';
            } elseif (!mysqli_select_db($connection, OB_DB_NAME)) {
                $error[] = 'Unable to select database (check database name).';
            }

            if (strlen(OB_HASH_SALT) < 6) {
                $errors[] = 'OB_HASH_SALT (password hash salt) is too short (<6 characters).';
            }

            if (stripos(OB_SITE, 'http://') !== 0 && stripos(OB_SITE, 'https://') !== 0) {
                $errors[] = 'OB_SITE (installation web address) is not valid.';
            } else {
                $curl = curl_init(OB_SITE);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($curl, CURLOPT_HEADER, true);
                curl_setopt($curl, CURLOPT_NOBODY, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($curl);
                curl_close($curl);

                if (!$response) {
                    $errors[] = 'OB_SITE (installation web address) is not valid or server did not reply.';
                } elseif (stripos($response, 'OpenBroadcaster-Application: index') === false) {
                    $errors[] = 'OB_SITE (installation web address) does not appear to point to a valid OpenBroadcaster installation.';
                }
            }

            if (!PHPMailer\PHPMailer\PHPMailer::ValidateAddress(OB_EMAIL_REPLY)) {
                $errors[] = 'OB_EMAIL_REPLY (email address used to send emails) is not valid.';
            }
            if (trim(OB_EMAIL_FROM) == '') {
                $errors[] = 'OB_EMAIL_FROM (email name used to send emails) must not be blank.';
            }

            if (defined('OB_MAGIC_FILE')) {
                if (!file_exists(OB_MAGIC_FILE)) {
                    $errors[] = 'OB_MAGIC_FILE (file identification database file) does not exist.';
                } else {
                    if (!finfo_open(FILEINFO_NONE, OB_MAGIC_FILE)) {
                        $errors[] = 'OB_MAGIC_FILE (file identification database file) is not valid or compatible.';
                    }
                }
            }

            if (defined('OB_THEME') && OB_THEME != 'default' && (!preg_match('/^[a-z0-9]+$/', OB_THEME) || !is_dir('themes/' . OB_THEME))) {
                $errors[] = 'OB_THEME (custom theme) is not a valid theme.';
            }

            if (defined('OB_REMOTE_DEBUG') && strlen(OB_REMOTE_DEBUG) < 4) {
                $errors[] = 'OB_REMOTE_DEBUG (remote.php debug code) must be 4 characters or longer.';
            }

            if (defined('OB_UPDATES_USER') && !OB_UPDATES_USER) {
                $errors[] = 'OB_UPDATES_USER (update area username) is not valid.';
            }
            if (defined('OB_UPDATES_PW') && !OB_UPDATES_PW) {
                $errors[] = 'OB_UPDATES_PW (update area password) is not valid.';
            }
            if (defined('OB_UPDATES_USER') && !defined('OB_UPDATES_PW')) {
                $errors[] = 'OB_UPDATES_USER (update area username) is set, but does not have an associated password (OB_UPDATES_PW).';
            }
            if (defined('OB_UPDATES_PW') && !defined('OB_UPDATES_USER')) {
                $errors[] = 'OB_UPDATES_PW (update area password) is set, but does not have an associated username (OB_UPDATES_USER).';
            }
        }

        if (!empty($errors)) {
            return array('Settings file',$errors,2);
        } else {
            return array('Settings file','Settings file (config.php) is valid.',0);
        }
    }

    public function directories_valid()
    {
        $errors = [];

        if (!is_dir(OB_MEDIA)) {
            $errors[] = 'OB_MEDIA (media directory) is not a valid directory.';
        } elseif (!is_writable(OB_MEDIA)) {
            $errors[] = 'OB_MEDIA (media directory) is not writable by the server.';
        }

        if (!is_dir(OB_MEDIA_UPLOADS)) {
            $errors[] = 'OB_MEDIA_UPLOADS (media unapproved/uploads directory) is not a valid directory.';
        } elseif (!is_writable(OB_MEDIA_UPLOADS)) {
            $errors[] = 'OB_MEDIA_UPLOADS (media upapproved/uploads directory) is not writable by the server.';
        }

        if (!is_dir(OB_MEDIA_ARCHIVE)) {
            $errors[] = 'OB_MEDIA_ARCHIVE (media archive directory) is not a valid directory.';
        } elseif (!is_writable(OB_MEDIA_ARCHIVE)) {
            $errors[] = 'OB_MEDIA_ARCHIVE (media archive directory) is not writable by the server.';
        }

        if (!is_dir(OB_CACHE)) {
            $errors[] = 'OB_CACHE (cache directory) is not a valid directory.';
        } elseif (!is_writable(OB_CACHE)) {
            $errors[] = 'OB_CACHE (cache directory) is not writable by the server.';
        }

        if (!is_dir(OB_ASSETS)) {
            $errors[] = 'The assets directory does not exist.';
        } elseif (!is_writable(OB_ASSETS)) {
            $errors[] = 'The assets directory is not writable by the server.';
        } elseif (!is_dir(OB_ASSETS . '/uploads')) {
            $errors[] = 'The assets/uploads directory does not exist.';
        } elseif (!is_writable(OB_ASSETS . '/uploads')) {
            $errors[] = 'The assets/uploads directory is not writable by the server.';
        }

        if ($errors) {
            return ['Directories', implode('. ', $errors), 2];
        } else {
            return ['Directories', 'Directories exist with valid permissions.', 0];
        }
    }

    public function composer()
    {
        if (!is_dir(__DIR__ . '/../vendor')) {
            return array('Composer', 'Missing vendor directory. Install composer then run "composer install" to get required dependencies.', 2);
        }

        return array('Composer', 'Vendor directory found. Run "composer install" to ensure all required packages are installed.',0);
    }

    public function npm()
    {
        if (!is_dir(__DIR__ . '/../node_modules')) {
            return array('Node Package Manager (NPM)', 'Missing node_modules directory. Install npm then run "npm install" to get required dependencies.', 1);
        }

        return array('Node Package Manager (NPM)', 'Node package directory found. Run "npm install" to ensure all required packages are installed.',0);
    }

    public function database_privileges()
    {
        $db = new OBFDB();
        $db->query('SELECT * FROM information_schema.schema_privileges WHERE 
            TABLE_SCHEMA = "' . $db->escape(OB_DB_NAME) . '" AND
            GRANTEE LIKE "\'' . $db->escape(OB_DB_USER) . '%"
        ');
        $privileges = [];
        foreach ($db->assoc_list() as $row) {
            $privileges[] = $row['PRIVILEGE_TYPE'];
        }

        $required = [
            'SELECT',
            'INSERT',
            'UPDATE',
            'DELETE',
            'CREATE',
            'DROP',
            'REFERENCES',
            'INDEX',
            'ALTER',
            'CREATE TEMPORARY TABLES',
            'LOCK TABLES',
            'EXECUTE',
            'CREATE VIEW',
            'SHOW VIEW',
            'CREATE ROUTINE',
            'ALTER ROUTINE',
            'EVENT',
            'TRIGGER'
        ];

        $missing = [];

        foreach ($required as $privilege) {
            if (array_search($privilege, $privileges) === false) {
                $missing[] = $privilege;
            }
        }

        if (!empty($missing)) {
            return array('Database Privileges', 'Database user may not have all the required privileges necessary. Missing: ' . implode(', ', $missing),1);
        }

        return array('Database Privileges', 'Found all necessary privileges.',0);
    }

    public function database_version()
    {
        $db = new OBFDB();
        $latest = 0;

        $db->where('name', 'dbver');
        $dbver = $db->get_one('settings');

        if (!$dbver) {
            return array('Database Version', 'Unable to determine present database version.  If this release is 2013-04-01 or older, please add the following to the settings table: name="dbver", value="20130401".',2);
        }

        $files = scandir(__DIR__);
        foreach ($files as $file) {
            $filename = pathinfo($file)['filename'];
            if (preg_match('/^[0-9]{8}$/', $filename)) {
                $latest = max($latest, (int) $filename);
            }
        }

        if ($dbver['value'] < $latest) {
            return ['Database Version', 'Database version ' . $dbver['value'] . ' lower than latest vesrion ' . $latest . '. Please run updates.', 1];
        } elseif ($dbver['value'] > $latest) {
            return ['Database Version', 'Database version ' . $dbver['value'] . ' greater than latest vesrion ' . $latest . '.', 1];
        }

        $this->dbver = $dbver['value'];

        return array('Database Version', 'Database version found: ' . $dbver['value'] . '.',0);
    }
}
