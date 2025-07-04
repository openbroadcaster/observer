<?php

namespace OpenBroadcaster\Remote;

class PlaylogPostAction extends BaseAction
{
    private $PlayersModel;

    public function __construct(object $player, object $request)
    {
        parent::__construct($player, $request);
    }

    public function run(): bool|object
    {
        if (empty($this->request->data)) {
            return (object) ['error' => 'missing XML post data'];
        }

        $data = new \SimpleXMLElement($this->request->data);
        $playlog = $data->playlog;

        foreach ($playlog->entry as $entry) {
            $entryArray = (array) $entry;

            // if a value is an object, that is because it has no value
            // in this case, it is left as a SimpleXMLElement object (empty) instead of being converted to a string.
            // so here we convert to a empty string
            foreach ($entryArray as $index => $value) {
                if (is_object($value)) {
                    $entryArray[$index] = '';
                }
            }

            // db inconsistency between web app and remote.
            $entryArray['timestamp'] = $entryArray['datetime'];
            unset($entryArray['datetime']);

            $entryArray['player_id'] = $this->player->id;

            $this->addedit_playlog($entryArray);
        }

        $output = ['playlog_post' => ['status' => 'success']];
        return (object) $output;
    }


    private function addedit_playlog($datatmp)
    {
        $useVals = ['player_id','media_id','artist','title','timestamp','context','emerg_id','notes'];
        foreach ($useVals as $val) {
            $data[$val] = $datatmp[$val];
        }

        // convert timestamp to mysql format
        // $data['datetime']=date('Y-m-d H:i:s',$data['datetime']);

        if ($this->verify_playlog($data)) {
            $this->db->insert('playlog', $data);
        }
    }

    private function verify_playlog($data)
    {
        foreach ($data as $key => $value) {
            $$key = $value;
            $dbcheck[] = '`' . $key . '`="' . $this->db->escape($value) . '"';
        }

        if (empty($player_id) || !isset($media_id) || empty($timestamp)) {
            $error = "Required field is missing.";
        } elseif ($context != 'show' && $context != 'emerg' && $context != 'fallback') {
            $error = "Context is invalid.";
        } elseif ($context == 'emerg' && !preg_match('/^[0-9]+$/', $emerg_id)) {
            $error = "Emergency broadcast ID is invalid or missing.";
        } elseif (!preg_match('/^[0-9]+$/', $player_id)) {
            $error = "Player ID is invalid.";
        } elseif (!preg_match('/^[0-9]+$/', $media_id)) {
            $error = "Media ID is invalid.";
        } else {
            $sql = 'select id from playlog where ' . implode(' and ', $dbcheck);

            $this->db->query($sql);

            // echo $this->db->error();

            if ($this->db->num_rows() > 0) {
                $error = 'This log entry already exists.';
            }
        }

        if (empty($error)) {
            return true;
        }

        return false;
    }
}
