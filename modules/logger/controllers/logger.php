<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

class Logger extends OBFController
{

	public function __construct()
	{

		parent::__construct();

		$this->user->require_permission('view_logger_log');
		$this->LoggerModel = $this->load->model('Logger');

	}

	public function viewLog()
	{

		$limit = $this->data('limit');
		$offset = $this->data('offset');

		$entries = $this->LoggerModel('logEntries',$limit,$offset);
		$total = $this->LoggerModel('logEntriesTotal');

		return array(true,'Log Entries.',array('entries'=>$entries, 'total'=>$total));
	}

	public function clearLog()
	{
		$this->LoggerModel('logClear');
		return array(true,'Log cleared');
	}

}
