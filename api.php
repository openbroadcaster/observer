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

require_once('components.php');

class OBFAPI
{

  private $load;
  private $user;
  private $io;
  private $callback_handler;

  public function __construct()
  {
    // handle rewrite API (v2)
    // we consider v1 to be the previous method via api.php.
    if(!(isset($_POST['m']) && is_array($_POST['m'])) && str_starts_with($_SERVER['REQUEST_URI'], '/api/v2/'))
    {
      if(preg_match('#^/api/v2/ping/?$#',$_SERVER['REQUEST_URI']))
      {
        echo json_encode('pong');
        exit();
      }

      $request = explode('/', substr($_SERVER['REQUEST_URI'], 8), 2);
      $_POST['c'] = $request[0] ?? null; // controller
      $_POST['a'] = $request[1] ?? null; // action/method
    }
  
    $this->io = OBFIO::get_instance();
    $this->load = OBFLoad::get_instance();
    $this->user = OBFUser::get_instance();
    $this->callback_handler = OBFCallbacks::get_instance();

    $auth_id = null;
    $auth_key = null;

    // we might get a post, or multi-post. standardize to multi-post.
    if(isset($_POST['m']) && is_array($_POST['m']))
      $requests = $_POST['m'];

    elseif(isset($_POST['c']) && isset($_POST['a']) && isset($_POST['d']))
    {
      $requests = array( array($_POST['c'],$_POST['a'],$_POST['d']) );
    }

    else
    {
      $this->io->error(OB_ERROR_BAD_POSTDATA);
      return;
    }
    
    // preliminary request validity check
    foreach($requests as $request)
    {
      if(!is_array($request) || count($request)!=3) { $this->io->error(OB_ERROR_BAD_POSTDATA); return; }
    }
    
    // try to get an ID/key pair for user authorization.
    if(!empty($_POST['i']) && !empty($_POST['k']))
    {
      $auth_id = $_POST['i'];
      $auth_key = $_POST['k'];
    }

    if (empty($_POST['appkey'])) {
      // authorize our user (from post data, cookie data, whatever.)
      $this->user->auth($auth_id,$auth_key);
    } else {
      $this->user->auth_appkey($_POST['appkey'], $requests);
    }
    
    // make sure each request has a valid controller (not done above since auth required before controller load)
    foreach($requests as $request)
    {
      if(!$this->load->controller($request[0])) { $this->io->error(OB_ERROR_BAD_POSTDATA); return; }
    }

    $responses = array();

    foreach($requests as $request)
    {
      $null = null; // for passing by reference.

      $controller = $request[0];
      $action = $request[1];

      // load our controller.
      $this->controller = $this->load->controller($controller);
      $this->controller->data = json_decode($request[2],true,512);

      // launch callbacks to be run before requested main process.
      // this is not passed to the main process (might be later if it turns out that would be useful...)
      $cb_name = get_class($this->controller).'.'.$action; // get Cased contrller name (get_class)
      $this->callback_handler->reset_retvals($cb_name); // reset any retvals stored from last request.
      $cb_return = $this->callback_handler->fire($cb_name,'init',$null,$this->controller->data);

      // do callbacks all main process to be run?
      if(empty($cb_return->r))
      {
        // run main process.
        $output = $this->controller->handle($action);
        $this->callback_handler->store_retval($cb_name,$cb_name,$output);

        // launch callbacks to be run after requested main process.
        // callbacks can manipulate output here.
        $cb_return = $this->callback_handler->fire($cb_name,'return',$null,$this->controller->data);

        // callback changes output.
        if(!empty($cb_return->r)) $output = $cb_return->v;
      }

      // init callbacks requested an early return.
      else $output = $cb_return->v;

      // output our response from the controller.
      if(!isset($output[2])) $output[2]=null;
      // $this->io->output(array('status'=>$output[0],'msg'=>$output[1],'data'=>$output[2]));
      $responses[] = array('status'=>$output[0],'msg'=>$output[1],'data'=>$output[2]);
    }

    // return first responce if we just had a single request. if multi-request, we return array of responses.
    if(!isset($_POST['m'])) $this->io->output($responses[0]);
    else $this->io->output($responses);

  }

}

$api = new OBFAPI();
