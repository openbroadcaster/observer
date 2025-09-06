<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// verify the OB installation. each method will be run, and should return array(NAME, DESCRIPTION (string or array), STATUS=success (0) / warning (1) / error (2) ).
// if error is returned, subsequent methods will not be run.
class OBFChecker
{
    public function __construct($module = null)
    {
        $this->module = $module;
    }

    public function php_version()
    {
        if (version_compare(phpversion(), '5.4', '<')) {
            return ['PHP Version','PHP v5.4 or higher is required (v' . phpversion() . ' detected).',2];
        }
        return ['PHP Version','PHP v' . phpversion() . ' detected.',0];
    }

    public function php_mysql_extension()
    {
        if (!extension_loaded('mysqli')) {
            return ['PHP MySQL extension', 'PHP MySQL extension not found.',2];
        }
        return ['PHP MySQL extension', 'PHP MySQL extension detected.',0];
    }

    public function php_extensions()
    {
        $errors = [];

        if (!extension_loaded('gd')) {
            $errors[] = 'GD extension not found.';
        }
        if (!extension_loaded('curl')) {
            $errors[] = 'cURL extension not found.';
        }
        if (!extension_loaded('fileinfo')) {
            $errors[] = 'Fileinfo extension not found.';
        }

        if (!extension_loaded('imagick')) {
            $errors[] = 'ImageMagick (imagick) extension not found.';
        }

        if (!empty($errors)) {
            return ['PHP Extensions',$errors,2];
        }
        return ['PHP Extensions','Required PHP extensions found.',0];
    }


    // there are currently no optional extensions, but this is kept as a placeholder.
    public function php_extensions_warning()
    {
        $errors = [];

        if (!empty($errors)) {
            return ['PHP Extensions',$errors,1];
        }
        return ['PHP Extensions','Optional PHP extensions found.',0];
    }

    public function which()
    {
        if (!exec('which which')) {
            return ['Program detection','Tool to detect dependencies (which) is not available.  Program detection will fail below.',1];
        }
        return ['Program detection','Tool to detect dependencies (which) is available.',0];
    }

    public function tts()
    {
        $oggenc = !!exec('which oggenc');
        $text2wave = !!exec('which text2wave');

        if (!$oggenc && !$text2wave) {
            return ['Text-to-Speech','Text-to-speech support requires programs oggenc and text2wave.  Install vorbis-tools and festival packages on Debian/Ubuntu.',1];
        } elseif (!$oggenc) {
            return ['Text-to-Speech','Text-to-speech support requires program oggenc.  Install vorbis-tools package on Debian/Ubuntu.',1];
        } elseif (!$text2wave) {
            return ['Text-to-Speech','Text-to-speech support requires program text2wave.  Install festival package on Debian/Ubinti.',1];
        } else {
            return ['Text-to-Speech','Required components for text-to-speech found.',0];
        }
    }

    public function avconv()
    {
        if (!exec('which ffmpeg')) {
            return ['Audio & Video Support','Audio and video support requires program ffmpeg. Install ffmpeg package on Debian/Ubuntu.',1];
        } else {
            return ['Audio & Video Support','FFmpeg found.  Audio and video formats should be supported.' . "\n\n" . 'Make sure the supporting libraries are installed (libavcodec-extra-53, libavdevice-extra-53, libavfilter-extra-2, libavutil-extra-51, libpostproc-extra-52, libswscale-extra-2 or similar packages on Debian/Ubuntu).',0];
        }
    }

    public function config_file_exists()
    {
        if (!file_exists(__DIR__ . '/../config.php')) {
            return ['Settings file', 'Settings file (config.php) not found.',2];
        }
        return ['Settings file','Settings file (config.php) found.  Will try to load components.php and config.php now.' . "\n\n" . 'If you see an error below (or if output stops), check config.php for errors.',0];
    }

    public function config_file_valid()
    {
        require_once(__DIR__ . '/../components.php');

        $fatal_error = false;
        $errors = [];

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
        if (!defined('OB_THUMBNAILS')) {
            $errors[] = 'OB_THUMBNAILS (thumbnails directory) not set.';
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
            }
        // else {
            //     $curl = curl_init(OB_SITE);
            //     curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            //     curl_setopt($curl, CURLOPT_HEADER, true);
            //     curl_setopt($curl, CURLOPT_NOBODY, true);
            //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            //     $response = curl_exec($curl);
            //     curl_close($curl);
            //
            //     if (!$response) {
            //         $errors[] = 'OB_SITE (installation web address) is not valid or server did not reply.';
            //     } elseif (stripos($response, 'OpenBroadcaster-Application: index') === false) {
            //         $errors[] = 'OB_SITE (installation web address) does not appear to point to a valid OpenBroadcaster installation.';
            //     }
            // }

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
            return ['Settings file',$errors,2];
        } else {
            return ['Settings file','Settings file (config.php) is valid.',0];
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

        if (!is_dir(OB_THUMBNAILS)) {
            $errors[] = 'OB_THUMBNAILS (thumbnails directory) is not a valid directory.';
        } elseif (!is_writable(OB_THUMBNAILS)) {
            $errors[] = 'OB_THUMBNAILS (thumbnails directory) is not writable by the server.';
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

        if (count($errors) == 0) {
            // make sure there are no directories specified within the OB_CACHE directory
            $cache = realpath(OB_CACHE);
            foreach ([OB_MEDIA, OB_MEDIA_UPLOADS, OB_MEDIA_ARCHIVE, OB_THUMBNAILS, OB_ASSETS] as $dir) {
                if (strpos(realpath($dir), $cache) === 0) {
                    $errors[] = 'Directory ' . $dir . ' is within the cache directory.';
                }
            }
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
            return ['Composer', 'Missing vendor directory. Install composer then run "composer install" to get required dependencies.', 2];
        }

        return ['Composer', 'Vendor directory found. Run "composer install" to ensure all required packages are installed.',0];
    }

    public function npm()
    {
        if (!is_dir(__DIR__ . '/../node_modules')) {
            return ['Node Package Manager (NPM)', 'Missing node_modules directory. Install npm then run "npm install" to get required dependencies.', 1];
        }

        return ['Node Package Manager (NPM)', 'Node package directory found. Run "npm install" to ensure all required packages are installed.',0];
    }

    public function database_privileges()
    {
        $db = new OBFDB();

        // compares the db name against TABLE_SCHEMA column which may use a wildcard %.
        $db->query('SELECT * FROM information_schema.schema_privileges WHERE
            "' . $db->escape(OB_DB_NAME) . '" LIKE TABLE_SCHEMA AND
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
            return ['Database Privileges', 'Database user may not have all the required privileges necessary. Missing: ' . implode(', ', $missing),1];
        }

        return ['Database Privileges', 'Found all necessary privileges.',0];
    }

    public function database_version()
    {
        $db = new OBFDB();
        $latest = 0;

        if ($this->module === null) {
            $db->where('name', 'dbver');
            $dbver = $db->get_one('settings');
        } else {
            $db->where('name', 'dbver-' . $this->module);
            $dbver = $db->get_one('settings');
        }

        if (!$dbver && $this->module === null) {
            return ['Database Version', 'Unable to determine present database version.  If this release is 2013-04-01 or older, please add the following to the settings table: name="dbver", value="20130401".',2];
        } elseif (!$dbver) {
            $db->insert('settings', [
                'name'  => 'dbver-' . $this->module,
                'value' => '20230101'
            ]);
            return ['Database Version', 'Version for module not yet set. Setting to 20230101 (no module updates possible before this year).', 0];
        }

        if ($this->module === null) {
            $files = scandir(__DIR__);
        } else {
            $dir = __DIR__ . "/../modules/{$this->module}/updates/";
            if (file_exists($dir)) {
                $files = scandir($dir);
            } else {
                $files = [];
            }
        }
        foreach ($files as $file) {
            $filename = pathinfo($file)['filename'];
            if (preg_match('/^[0-9]{8}$/', $filename)) {
                $latest = max($latest, (int) $filename);
            }
        }

        $this->dbver = $dbver['value'];

        if ($dbver['value'] < $latest) {
            return ['Database Version', 'Database version ' . $dbver['value'] . ' lower than latest version ' . $latest . '. Please run updates.', 1];
        } elseif ($dbver['value'] > $latest) {
            return ['Database Version', 'Database version ' . $dbver['value'] . ' greater than latest version ' . $latest . '.', 1];
        }

        return ['Database Version', 'Database version found: ' . $dbver['value'] . '.',0];
    }
}
