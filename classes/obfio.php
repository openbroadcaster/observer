<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

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

/**
 * Input/Output class. Outputs data and error messages.
 *
 * @package Class
 */
class OBFIO
{
  public function __construct()
  {

  }

  /**
   * Create an instance of OBFIO or return the already-created instance.
   *
   * @return instance
   */
  public static function &get_instance()
  {

    static $instance;

    if (isset($instance)) {
      return $instance;
    }

    $instance = new OBFIO();

    return $instance;

  }

  /**
   * Echo JSON encoded error message based on the error code. Errors include
   * OB_ERROR_BAD_POSTDATA, OB_ERROR_BAD_CONTROLLER, OB_ERROR_BAD_DATA, and
   * OB_ERROR_DENIED.
   *
   * @param error_no
   */
  public function error($error_no)
  {

    $user = OBFUser::get_instance();

    switch($error_no)
    {
      case OB_ERROR_BAD_POSTDATA:
        $msg = 'Invalid POST data.';
        break;

      case OB_ERROR_BAD_CONTROLLER:
        $msg = 'Invalid controller.';
        break;

      case OB_ERROR_BAD_DATA:
        $msg = 'Invalid controller data.';
        break;

      case OB_ERROR_DENIED:
        $msg = 'Access denied.';
        break;

    }

    $this->output(array('error'=> array('no'=>$error_no,'msg'=>$msg,'uid'=>$user->param('id'))));

  }

  /**
   * JSON-encode data and echo it.
   *
   * @param data
   */
  public function output($data)
  {

    echo json_encode($data);

  }


}
