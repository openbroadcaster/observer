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

// set some constants
if(!defined('OB_ERROR_BAD_POSTDATA')) define('OB_ERROR_BAD_POSTDATA',1);
if(!defined('OB_ERROR_BAD_CONTROLLER')) define('OB_ERROR_BAD_CONTROLLER',2);
if(!defined('OB_ERROR_BAD_DATA')) define('OB_ERROR_BAD_DATA',3);
if(!defined('OB_ERROR_DENIED')) define('OB_ERROR_DENIED',4);
if(!defined('OB_LOCAL')) define('OB_LOCAL',__DIR__);

// use same working directory regardless of where our script is.
chdir(OB_LOCAL); 

// load config
if(!file_exists('config.php')) die('Settings file (config.php) not found.');
require('config.php');

// set defaults if not set
if(!defined('OB_ASSETS')) define('OB_ASSETS',OB_LOCAL.'/assets');
if(!defined('OB_MEDIA_FILESIZE_LIMIT')) define('OB_MEDIA_FILESIZE_LIMIT',1024);
if(!defined('OB_MEDIA_VERIFY')) define('OB_MEDIA_VERIFY',true);

// most things are done in UTC.  sometimes the tz is set to the player's tz for a 'strtotime' +1month,etc. type calculation which considers DST.
date_default_timezone_set('Etc/UTC'); 

// load core components
require('classes/obfdb.php');
require('classes/obfload.php');
require('classes/obfio.php');
require('classes/obfcontroller.php');
require('classes/obfcallbacks.php');
require('classes/obfhelpers.php');
require('classes/obfmodel.php');
require('classes/obfmodels.php');
require('classes/obfuser.php');
require('classes/obfmodule.php');

// load third party components
require('extras/PHPMailer/src/Exception.php');
require('extras/PHPMailer/src/PHPMailer.php');