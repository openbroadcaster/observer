<?php

namespace OpenBroadcaster\Remote;

class EmergAction extends BaseAction
{
    public function run(): bool|object
    {
        $buffer = false;
        if (isset($this->request->buffer)) {
            $buffer = $this->request->buffer * 86400;
        } elseif (isset($this->request->hbuffer)) {
            $buffer = $this->request->hbuffer * 3600;
        }

        if (!$buffer) {
            $this->error = 'Emerg action requires a buffer (days) or hbuffer (hours) parameter.';
            return false;
        }

        if ($this->player->parent_player_id && $this->player->use_parent_alert) {
            $broadcasts = $this->get_upcoming_emergency_broadcasts($this->player->parent_player_id, time() + $buffer);
        } else {
            $broadcasts = $this->get_upcoming_emergency_broadcasts($this->player->id, time() + $buffer);
        }

        $output = [
            'emergency_broadcasts' => ['broadcast' => []]
        ];

        if (!empty($broadcasts)) {
            foreach ($broadcasts as $broadcast) {
                $this->db->where('id', $broadcast['item_id']);
                $mediaInfo = $this->db->get_one('media');

                if (empty($broadcast['duration'])) {
                    $broadcast_duration = $mediaInfo['duration'];
                } else {
                    $broadcast_duration = $broadcast['duration'];
                }

                if (!empty($mediaInfo['is_archived'])) {
                    $filerootdir = OB_MEDIA_ARCHIVE;
                } elseif (!empty($mediaInfo['is_approved'])) {
                    $filerootdir = OB_MEDIA;
                } else {
                    $filerootdir = OB_MEDIA_UPLOADS;
                }
                $fullfilepath = $filerootdir . '/' . $mediaInfo['file_location'][0] . '/' . $mediaInfo['file_location'][1] . '/' . $mediaInfo['filename'];
                $filesize = filesize($fullfilepath);

                // set start if we don't have one... (starts immediately).  remote wants something here.
                if (empty($broadcast['start'])) {
                    $broadcast['start'] = '0';
                }

                // set end if we don't have one... (plays indefinitely).  remote wants something here.
                if (empty($broadcast['stop'])) {
                    $broadcast['stop'] = '2147483647';
                }

                $item = (object) [];

                $item->id = $broadcast['id'];
                $item->start_timestamp = $broadcast['start'];
                $item->end_timestamp = $broadcast['stop'];
                $item->frequency = $broadcast['frequency'];
                $item->artist = htmlspecialchars($mediaInfo['artist']);
                $item->filename = htmlspecialchars($mediaInfo['filename']);
                $item->title = htmlspecialchars($mediaInfo['title']);
                $item->media_id = htmlspecialchars($mediaInfo['id']);
                $item->duration = htmlspecialchars($broadcast_duration);
                $item->media_type = htmlspecialchars($mediaInfo['type']);
                $item->hash = $mediaInfo['file_hash'];
                $item->filesize = $filesize;
                $item->location = $mediaInfo['file_location'];
                $item->archived = $mediaInfo['is_archived'];
                $item->approved = $mediaInfo['is_approved'];
                $item->mode = $broadcast['mode'];

                // get voicetrack properties if that mode is set
                if ($broadcast['mode'] === 'voicetrack') {
                    $item->voicetrack_settings = (object) [];

                    $properties = json_decode($broadcast['properties'], true);

                    $item->voicetrack_settings->volume = $properties['voicetrack_volume'];
                    $item->voicetrack_settings->fadeout_before = $properties['voicetrack_fadeout_before'];
                    $item->voicetrack_settings->fadein_after = $properties['voicetrack_fadein_after'];
                }

                $output['emergency_broadcasts']['broadcast'][] = $item;
            }
        }

        return (object) $output;
    }

    private function get_upcoming_emergency_broadcasts($playerId, $timeLimit)
    {
        $now = time();

        $addsql = ' where player_id=' . $playerId . ' and (start<=' . $timeLimit . ' or start IS NULL) and (stop>' . $now . ' or stop IS NULL ) ';
        $sql = 'select *,TIME_TO_SEC(duration) as duration from alerts' . $addsql . ' order by start';

        $this->db->query($sql);
        $r = $this->db->assoc_list();

        return $r;
    }
}
