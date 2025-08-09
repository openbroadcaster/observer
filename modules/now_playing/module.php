<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

class NowPlayingModule extends OBFModule
{

	public $name = 'Now Playing v1.0';
	public $description = 'Provide "now playing" information on page at <IP_of_Server>/modules/now_playing/now_playing.php?i=playerID';

	public function callbacks()
	{

	}

	public function install()
	{
		return true;
	}

	public function uninstall()
	{
		return true;
	}

	public function purge()
	{
		return true;
	}

}
