<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

class LoggerModule extends OBFModule
{

    public $name = 'Logger v1.0';
    public $description = 'Log all controller functions.';

    public function callbacks()
    {

        $hooks = array();

        $hooks[] = 'Account.login';
        $hooks[] = 'Account.uid';
        $hooks[] = 'Account.permissions';
        $hooks[] = 'Account.logout';
        $hooks[] = 'Account.settings';
        $hooks[] = 'Account.update_profile';
        $hooks[] = 'Account.update_settings';
        $hooks[] = 'Account.forgotpass';
        $hooks[] = 'Account.newaccount';
        $hooks[] = 'Account.key_new';
        $hooks[] = 'Account.key_delete';
        $hooks[] = 'Account.key_permissions_save';
        $hooks[] = 'Account.key_load';
        $hooks[] = 'Account.store';

        $hooks[] = 'Player.search';
        $hooks[] = 'Player.save';
        $hooks[] = 'Player.delete';
        $hooks[] = 'Player.get';
        $hooks[] = 'Player.station_id_avg_duration';
        $hooks[] = 'Player.monitor_search';
        $hooks[] = 'Player.now_playing';

        $hooks[] = 'Emergency.get';
        $hooks[] = 'Emergency.search';
        $hooks[] = 'Emergency.save';
        $hooks[] = 'Emergency.delete';

        $hooks[] = 'Media.formats_get';
        $hooks[] = 'Media.formats_save';
        $hooks[] = 'Media.search';
        $hooks[] = 'Media.save';
        $hooks[] = 'Media.archive';
        $hooks[] = 'Media.unarchive';
        $hooks[] = 'Media.delete';
        $hooks[] = 'Media.get';

        $hooks[] = 'Modules.search';
        $hooks[] = 'Modules.install';
        $hooks[] = 'Modules.uninstall';

        $hooks[] = 'Playlist.get';
        $hooks[] = 'Playlist.search';
        $hooks[] = 'Playlist.save';
        $hooks[] = 'Playlist.validate_dynamic_properties';
        $hooks[] = 'Playlist.delete';

        $hooks[] = 'Shows.get';
        $hooks[] = 'Shows.search';
        $hooks[] = 'Shows.delete';
        $hooks[] = 'Shows.save';

        $hooks[] = 'Timeslots.get';
        $hooks[] = 'Timeslots.search';
        $hooks[] = 'Timeslots.delete';
        $hooks[] = 'Timeslots.save';

        $hooks[] = 'Settings.category_list';
        $hooks[] = 'Settings.category_edit';
        $hooks[] = 'Settings.category_delete';
        $hooks[] = 'Settings.category_get';
        $hooks[] = 'Settings.genre_list';
        $hooks[] = 'Settings.genre_edit';
        $hooks[] = 'Settings.genre_delete';
        $hooks[] = 'Settings.genre_get';
        $hooks[] = 'Settings.country_list';
        $hooks[] = 'Settings.language_list';

        $hooks[] = 'Users.group_list';
        $hooks[] = 'Users.user_list';
        $hooks[] = 'Users.user_manage_list';
        $hooks[] = 'Users.user_manage_addedit';
        $hooks[] = 'Users.user_manage_delete';
        $hooks[] = 'Users.permissions_manage_delete';
        $hooks[] = 'Users.permissions_manage_addedit';
        $hooks[] = 'Users.permissions_manage_list';

        foreach($hooks as $hook)
            $this->callback_handler->register_callback('LoggerModel.log',$hook,'return',0);

    }

    public function install()
    {

        $this->db->query(<<<SQL
        CREATE TABLE IF NOT EXISTS `module_logger` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `datetime` int(10) unsigned NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            `controller` varchar(255) NOT NULL,
            `action` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
        SQL);

        $this->permission_enable('administration', 'view_logger_log', 'view log produced by logger module');

        return true;

    }

    public function uninstall()
    {
        $this->permission_disable('view_logger_log');

        return true;
    }

    public function purge()
    {
        $this->db->query('DROP TABLE IF EXISTS `module_logger`');

        $this->permission_delete('view_logger_log');

        return true;
    }

}
