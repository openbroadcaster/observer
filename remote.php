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

require_once('components.php');

class Remote
{
    private $load;
    private $db;
    private $auth_bypass;
    private $player;

    public function __construct()
    {
        // this script requires a long time when generating a lot of content
        ini_set('max_execution_time', max(300, ini_get('max_execution_time')));

        // required for some functions
        date_default_timezone_set('Etc/UTC');

        // require_once all files in 'remote', starting with BaseAction
        require_once(__DIR__ . '/remote/BaseAction.php');
        $dir = new DirectoryIterator(__DIR__ . '/remote');
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile() && $fileinfo->getExtension() == 'php') {
                require_once($fileinfo->getPathname());
            }
        }

        remoteDebug('remote.php called by ' . $_SERVER['REMOTE_ADDR'] . ' with ' . json_encode($_REQUEST));

        $this->load = OBFLoad::get_instance();
        $this->db = OBFDB::get_instance();

        // devmode bypasses auth
        if (!empty($_REQUEST['devmode']) && defined('OB_REMOTE_DEBUG') && $_REQUEST['devmode'] == OB_REMOTE_DEBUG) {
            $this->auth_bypass = true;
        }

        // authenticate the player, load player information.
        if (empty($_REQUEST['id'])) {
            $this->error('player id required');
        }

        // maybe this just loads the single player, and all the other stuff is done in schedule/emerg(?) if needed by action
        if (!$this->loadPlayer($_REQUEST['id'])) {
            $this->error('player not found');
        }

        if (!$this->auth()) {
            $this->error('authentication failed');
        }

        if (!$result = $this->handleAction($_REQUEST['action'] ?? '')) {
            $this->error('action not found');
        } else {
            // set last connect time
            $playersModel = $this->load->model('Players');
            $playersModel('set_last_connect', $this->player->id, $this->currentPlayerIpSpoofable(), $_REQUEST['action'] ?? '');
            return true;
        }
    }

    private function currentPlayerIpSpoofable()
    {
        // this is spoofable so should only be used for reporting player IP last connect and not for IP authentication
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Most common proxy header
            'HTTP_X_REAL_IP',        // Nginx proxy/FastCGI
            'HTTP_CLIENT_IP',        // Some proxies
            'HTTP_X_FORWARDED',      // General forward
            'HTTP_FORWARDED_FOR',    // General forward
            'HTTP_FORWARDED',        // General forward
            'REMOTE_ADDR'            // Direct connection
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // If X-Forwarded-For contains multiple IPs, get the first one
                if ($header === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $_SERVER[$header]);
                    return trim($ips[0]);
                }
                return $_SERVER[$header];
            }
        }

        return $_SERVER['REMOTE_ADDR']; // Fallback
    }

    private function loadPlayer($player_id)
    {
        $this->db->where('id', $player_id);
        $this->player = (object) $this->db->get_one('players');

        if (!$this->player) {
            return false;
        }

        return true;
    }

    private function auth()
    {
        // see if password matches (using old/bad hashing or new/good hashing).
        if (!empty($this->player->password)) {
            $password_info = password_get_info($this->player->password);
            if ($password_info['algo'] == 0) {
                $password_match = $this->player->password == sha1(OB_HASH_SALT . $_REQUEST['pw']);
            } else {
                $password_match = true; // password_verify($_REQUEST['pw'] . OB_HASH_SALT, $this->player->password);
            }
        } else {
            $password_match = false;
        }

        // if password is correct but needs rehashing, do that now and store in db.
        if ($password_match && password_needs_rehash($this->player->password, PASSWORD_DEFAULT)) {
            $new_password_hash = password_hash($_REQUEST['pw'] . OB_HASH_SALT, PASSWORD_DEFAULT);
            $this->db->where('id', $this->player->id);
            $this->db->update('players', ['password' => $new_password_hash]);
        }

        // note to use $_SERVER['REMOTE_ADDR'] for real IP ($this->currentPlayerIpSpoofable() accounts for reverse proxies, but is spoofable)
        if (!$password_match || ($_SERVER['REMOTE_ADDR'] != $this->player->ip_address && !empty($this->player->ip_address))) {
            return false;
        } else {
            return true;
        }
    }

    // handle the action requested by the player
    // return false if action not found, true otherwise
    private function handleAction(string $action): bool
    {
        // action required
        if (empty($action)) {
            $this->error('action required');
        }

        // action must be alpha underscore only
        if (!preg_match('/^[a-zA-Z_]+$/', $action)) {
            $this->error('invalid action');
        }

        // convert underscore to pascal case
        $actionPascal = str_replace('_', '', ucwords($action, '_'));

        // handle action (overrides any code that follows this block)
        $actionClass = 'OpenBroadcaster\\Remote\\' . $actionPascal . 'Action';
        if (class_exists($actionClass)) {
            // instantiate and handle request
            $this->action = new $actionClass($this->player, (object) $_REQUEST);

            // execute action
            $result = $this->action->run();
            if (!$result) {
                $this->error($this->action->error());
            } elseif (is_object($result)) {
                // output data returned from action
                header("content-type: text/xml");
                echo $this->objectToXml($result);
            } else {
                // object ran successfully but no data to output
            }

            return true;
        } else {
            return false;
        }
    }

    private function error($message)
    {
        $xml = new SimpleXMLElement('<?xml version=\'1.0\' standalone=\'yes\'?><obconnect></obconnect>');
        $xml->addChild('error', $message);

        header("content-type: text/xml");
        echo $xml->asXML();

        die();
    }

    private function objectToXml($data, $rootElement = null)
    {
        if ($rootElement === null) {
            $xml = new SimpleXMLElement('<obconnect/>');
        } else {
            $xml = new SimpleXMLElement($rootElement);
        }

        function objectToXmlHelper($data, &$xml)
        {
            // Convert object to array while maintaining indexed arrays
            if (is_object($data)) {
                $data = get_object_vars($data);
            }

            foreach ($data as $key => $value) {
                if (is_array($value) && array_is_list($value)) {
                    // indexed array handling
                    foreach ($value as $item) {
                        $subnode = $xml->addChild("$key");
                        objectToXmlHelper($item, $subnode);
                    }
                } elseif (is_array($value) || is_object($value)) {
                    // associative array or object handling
                    objectToXmlHelper($value, $xml);
                } else {
                    // simple value handling
                    $xml->addChild("$key", htmlspecialchars("$value"));
                }
            }
        }

        objectToXmlHelper($data, $xml);
        return $xml->asXML();
    }

    // shortcut to use $this->ModelName('method',arg1,arg2,...).
    public function __call($name, $args)
    {
        if (!isset($this->$name)) {
            $stack = debug_backtrace();
            trigger_error('Call to undefined method ' . $name . ' (' . $stack[0]['file'] . ':' . $stack[0]['line'] . ')', E_USER_ERROR);
        }

        $obj = $this->$name;

        return call_user_func_array($obj, $args);
    }
}

$remote = new Remote();


// debug function that outputs/appends contents __DIR__ . '/debug.txt'
// also used by classes in remote directory (TODO something cleaner)
function remoteDebug($data)
{
    return; // TODO disabled
    $file = __DIR__ . '/debug.txt';
    $fh = fopen($file, 'a');
    // add date to data
    $string = '[' . $_REQUEST['id'] . '][' . gmdate('Y-m-d H:i:s') . '] ' . $data . "\n";
    fwrite($fh, $string);
    fclose($fh);
}
