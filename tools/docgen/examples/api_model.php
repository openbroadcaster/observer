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
 * Model example class.
 *
 * @package OBFModel
 */
class ApiModel extends OBFModel
{

  private $api_url;
  private $api_user;
  private $api_pass;

  private $api_auth_id;
  private $api_auth_key;

  public function set_url($url)
  {
    $this->api_url = $url;
  }

  public function set_user($user)
  {
    $this->api_user = $user;
  }

  public function set_pass($pass)
  {
    $this->api_pass = $pass;
  }

  public function upload($file)
  {
    // uploads can take a while...
    set_time_limit(3600);

    if(!$this->api_url) return false;

    // login required for file upload
    if(!$this->api_auth_id)
    {
      $login_response = $this->login();
      if($login_response->status==false) return $login_response;
    }

    $ch = curl_init($this->api_url.'upload.php');

    // we have login information. provide as cookie.  (ob_auth_id, ob_auth_key)
    curl_setopt($ch,CURLOPT_COOKIE,'ob_auth_id='.$this->api_auth_id.'; ob_auth_key='.$this->api_auth_key);

    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_PUT,true);
    curl_setopt($ch,CURLOPT_INFILE,fopen($file,'r'));
    curl_setopt($ch,CURLOPT_INFILESIZE,filesize($file));
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_LOW_SPEED_LIMIT,512); // lower speed limit of 0.5KB/s
    curl_setopt($ch,CURLOPT_LOW_SPEED_TIME,10); // cancels if going this slow for 10s or more.

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response);

  }

  public function call($controller,$action,$data=null,$login_required=true)
  {
    if(!$this->api_url) return false;

    if($login_required && !$this->api_auth_id)
    {
      $login_response = $this->login();
      if($login_response->status==false) return $login_response;
    }

    $post = array();

    $post['c'] = $controller;
    $post['a'] = $action;
    $post['d'] = json_encode($data);

    $post['i'] = $this->api_auth_id;
    $post['k'] = $this->api_auth_key;

    $ch = curl_init($this->api_url.'api.php');

    // we have login information. provide as cookie.  (ob_auth_id, ob_auth_key)
    if($this->api_auth_id)
    {
      curl_setopt($ch,CURLOPT_COOKIE,'ob_auth_id='.$this->api_auth_id.'; ob_auth_key='.$this->api_auth_key);
    }

    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($post));
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response);
  }

  public function login()
  {
    $response = $this->call('account','login',array('username'=>$this->api_user,'password'=>$this->api_pass),false);

    if($response->status==true)
    {
      $this->api_auth_id = $response->data->id;
      $this->api_auth_key = $response->data->key;
    }

    return $response;

  }


}
