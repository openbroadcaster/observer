<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Logger example module.
 *
 * @package Controller.Logger
 */
namespace OpenBroadcaster\Modules\Logger\Controllers;

use OpenBroadcaster\Base\Controller;

class Logger extends Controller
{

	public function __construct()
	{

		parent::__construct();

		$this->user->require_permission('view_logger_log');
		$this->LoggerModel = $this->load->model('Logger', 'Logger');

	}

	/**
	 * View log.
	 *
	 * @param limit
	 * @param offset
	 * @return [entries, total]
	 *
	 * @route GET /logger
	 */
	public function viewLog()
	{

		$limit = $this->data('limit');
		$offset = $this->data('offset');

		$entries = $this->LoggerModel('logEntries',$limit,$offset);
		$total = $this->LoggerModel('logEntriesTotal');

		return array(true,'Log Entries.',array('entries'=>$entries, 'total'=>$total));
	}

	/**
	 * Clear log.
	 *
	 * @route DELETE /logger
	 */
	public function clearLog()
	{
		$this->LoggerModel('logClear');
		return array(true,'Log cleared');
	}

}
