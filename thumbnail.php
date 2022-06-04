<?php

/*     
    Copyright 2012-2021 OpenBroadcaster, Inc.

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

require('components.php');

// COMPLETE AUTHENTICATION, usually handled by api.php
$user = OBFUser::get_instance();

$auth_id = null;
$auth_key = null;

// try to get an ID/key pair for user authorization.
if(!empty($_POST['i']) && !empty($_POST['k'])) 
{
  $auth_id = $_POST['i'];
  $auth_key = $_POST['k'];
}

// if not in post, try fetching from cookie.
elseif(!empty($_COOKIE['ob_auth_id']) && !empty($_COOKIE['ob_auth_key']))
{
  $auth_id = $_COOKIE['ob_auth_id'];
  $auth_key = $_COOKIE['ob_auth_key'];
} 

// authorize our user (from post data, cookie data, whatever.)
$user->auth($auth_id,$auth_key);

class MediaThumbnail extends OBFController
{
  public function not_found()
  {
    http_response_code(404);
    die();
  }

  public function output($id)
  {
    global $user;

    $this->db->where('id',$id);
    $media = $this->db->get_one('media');
    if(!$media) $this->not_found();
    
    // check permissions
    if($media['status']!='public')
    {
      $user->require_authenticated();
      $is_media_owner = $media['owner_id']==$user->param('id');    
      if($media['status']=='private' && !$is_media_owner) $user->require_permission('manage_media');
    }
    
    $l0 = $media['file_location'][0];
    $l1 = $media['file_location'][1];
    $file = OB_CACHE.'/thumbnails/'.$l0.'/'.$l1.'/'.$media['id'].'.jpg';
    if(!file_exists($file)) $this->not_found();
    header('Content-Type: image/jpeg');
    readfile($file);
  }
}

$thumbnail = new MediaThumbnail();
$thumbnail->output($_GET['id']);
