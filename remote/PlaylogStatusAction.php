<?php

namespace OpenBroadcaster\Remote;

class PlaylogStatusAction extends BaseAction
{
    private $PlayersModel;

    public function __construct(object $player, object $request)
    {
        parent::__construct($player, $request);
    }

    public function run(): bool|object
    {
        $this->db->where('player_id', $this->player->id);
        $this->db->orderby('timestamp', 'desc');
        $last_entry = $this->db->get_one('playlog');

        if (empty($last_entry)) {
            $last_timestamp = 0;
        } else {
            $last_timestamp = $last_entry['timestamp'];
        }

        $output = ['playlog_status' => ['last_timestamp' => $last_timestamp]];
        return (object) $output;
    }
}
