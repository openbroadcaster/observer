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


if(php_sapi_name()!='cli') die('cli only');

$dirs = scandir(__DIR__.'/../themes/');

foreach($dirs as $dir)
{
  $fulldir = realpath(__DIR__.'/../themes/'.$dir);
  if($dir[0]=='.' || !is_dir($fulldir) || !is_file($fulldir.'/style.scss')) continue;

  $command = 'sass -s compressed '.escapeshellarg($fulldir.'/style.scss').' '.escapeshellarg($fulldir.'/style.css');
  passthru($command);
}
