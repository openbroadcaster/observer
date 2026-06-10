<?php

namespace OpenBroadcaster\Remote;

class NowPlaying extends \OpenBroadcaster\Base\Remote
{
    private $PlayersModel;

    public function __construct(object $player, object $request)
    {
        parent::__construct($player, $request);
    }

    public function run(): bool|object
    {
        $current_playlist_id = trim($this->request->playlist_id);
        $current_playlist_end = trim($this->request->playlist_end);
        $current_media_id = trim($this->request->media_id);
        $current_media_end = trim($this->request->media_end);
        $current_show_name = trim($this->request->show_name);

        if (!preg_match('/^[0-9]+$/', $current_playlist_id) || empty($current_playlist_id)) {
            $current_playlist_id = null;
        }
        if (!preg_match('/^[0-9]+$/', $current_playlist_end) || empty($current_playlist_end)) {
            $current_playlist_end = null;
        }
        if (!preg_match('/^[0-9]+$/', $current_media_id) || empty($current_media_id)) {
            $current_media_id = null;
        }
        if (!preg_match('/^[0-9]+$/', $current_media_end) || empty($current_media_end)) {
            $current_media_end = null;
        }

        if ($current_show_name == '') {
            $current_show_name = null;
        }

        // update current item in players table
        $db['current_playlist_id'] = $current_playlist_id;
        $db['current_playlist_end'] = $current_playlist_end;
        $db['current_media_id'] = $current_media_id;
        $db['current_media_end'] = $current_media_end;
        $db['current_show_name'] = $current_show_name;

        $this->db->where('id', $this->request->id);
        $this->db->update('players', $db);

        // add entry to players log, skipping if identical to the last entry for this player
        $this->db->where('player_id', $this->request->id);
        $this->db->orderby('id', 'desc');
        $last = $this->db->get_one('players_log');

        if (
            !$last ||
            $last['media_id'] != $current_media_id ||
            $last['playlist_id'] != $current_playlist_id ||
            $last['media_end'] != $current_media_end ||
            $last['playlist_end'] != $current_playlist_end
        ) {
            $entry = [
                'player_id'    => $this->request->id,
                'timestamp'    => time(),
                'media_id'     => $current_media_id,
                'playlist_id'  => $current_playlist_id,
                'media_end'    => $current_media_end,
                'playlist_end' => $current_playlist_end,
                'show_name'    => $current_show_name
            ];
            $this->db->insert('players_log', $entry);
        }

        return true;
    }
}
