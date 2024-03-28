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

class LoggerModel extends OBFModel
{

	public function log($hook,$position,&$args)
	{

		$class_method = explode('.',$hook);
		$class = $class_method[0];
		$method = $class_method[1];

		$data = array();
		$data['datetime'] = time();
		$data['user_id'] = $this->user->param('id');
		$data['controller'] = $class;
		$data['action'] = $method;

		$this->db->insert('module_logger',$data);

		return new OBFCallbackReturn;
	}

	// call after logEntries to get total results.
	public function logEntriesTotal()
	{
		return $this->logTotal;
	}

	public function logEntries($limit=null,$offset=null)
	{

		if($limit) $this->db->limit($limit);
		if($offset) $this->db->offset($offset);

		$this->db->what('module_logger.*');
		$this->db->what('users.display_name','user_name');
		$this->db->leftjoin('users','module_logger.user_id','users.id');
		$this->db->orderby('datetime','desc');

    $this->db->calc_found_rows();

		$entries = $this->db->get('module_logger');

		$this->logTotal = $this->db->found_rows();

		return $entries;

	}

	public function logClear()
	{
		$this->db->query('truncate table module_logger');
		return true;
	}

}
