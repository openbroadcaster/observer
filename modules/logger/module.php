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

		$this->db->query('CREATE TABLE IF NOT EXISTS `module_logger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `controller` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');

		$data = array();
		$data['name'] = 'view_logger_log';
		$data['description'] = 'view log produced by logger module';
		$data['category'] = 'administration';

		$this->db->insert('users_permissions',$data);

		return true;

	}

	public function uninstall()
	{
		$this->db->where('name','view_logger_log');
		$this->db->delete('users_permissions');

		return true;
	}

	public function purge()
	{
		$this->db->query('DROP TABLE IF NOT EXISTS `module_logger`');
	}

}
