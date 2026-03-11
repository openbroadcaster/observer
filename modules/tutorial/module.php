<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

use OpenBroadcaster\Base\Module;

class TutorialModule extends Module
{

	public $name = 'Tutorial v1.0';
	public $description = 'Interactive tutorial running through the basics of OBServer.';

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
