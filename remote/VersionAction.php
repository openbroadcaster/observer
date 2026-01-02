<?php

namespace OpenBroadcaster\Remote;

class VersionAction extends BaseAction
{
    public function __construct(object $player, object $request)
    {
        parent::__construct($player, $request);
    }

    public function run(): bool|object
    {
        $playersModel = $this->load->model('Players');

        if (!empty($this->request->version)) {
            $playersModel('update_version', $this->player->id, $this->request->version);

            //add function to update location
            if (!empty($this->request->longitude) || !empty($this->request->latitude)) {
                $playersModel('update_location', $this->player->id, $this->request->longitude, $this->request->latitude);
            }
        }

        if (is_file('VERSION')) {
            $version = trim(file_get_contents('VERSION'));
            header("content-type: application/json");
            echo json_encode($version);
            die(); // not XML return format, so not having remote.php handle
        }

        return true;
    }
}
