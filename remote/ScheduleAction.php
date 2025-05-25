<?php

namespace OpenBroadcaster\Remote;

class ScheduleAction extends BaseAction
{
    private $PlayersModel;

    public function __construct(object $player, object $request)
    {
        parent::__construct($player, $request);
    }

    public function run(): bool|object
    {
        $this->buffer = false;
        if (isset($this->request->buffer)) {
            $this->buffer = $this->request->buffer * 86400;
        } elseif (isset($this->request->hbuffer)) {
            $this->buffer = $this->request->hbuffer * 3600;
        }

        if (!$this->buffer) {
            $this->error = 'Schedule action requires a buffer (days) or hbuffer (hours) parameter.';
            return false;
        }

        $this->loadRelatedPlayers();

        // load models
        $this->ShowsModel = $this->load->model('Shows');
        $this->PlaylistsModel = $this->load->model('Playlists');
        $this->MediaModel = $this->load->model('Media');
        $this->TimeslotsModel = $this->load->model('Timeslots');

        // a little buffer...
        $localtime = strtotime("-1 minute");

        // start new xml object
        $this->xml = new \SimpleXMLElement('<?xml version=\'1.0\' standalone=\'yes\'?><obconnect></obconnect>');

        // build the schedule XML
        $schedxml = $this->xml->addChild('schedule');

        $end_timestamp = $localtime + $this->buffer + 60;

        $shows = $this->ShowsModel('get_shows', $localtime, $end_timestamp, $this->schedule_player_id);

        $show_times = [];

        foreach ($shows as $show) {
            // create start datetime object (used for playlist resolve)
            $show_start = new \DateTime('@' . $show['start'], new \DateTimeZone('UTC'));
            $show_start->setTimezone(new \DateTimeZone($this->player->timezone));

            // skip this show if linein but not supported (will get default playlist instead later if available)
            if ($show['item_type'] == 'linein' && empty($this->player->support_linein)) {
                continue;
            }

            $media_items = false;

            $showxml = $schedxml->addChild('show');
            $showxml->addChild('id', $show['id']);
            $showxml->addChild('date', gmdate('Y-m-d', $show['start']));
            $showxml->addChild('time', gmdate('H:i:s', $show['start']));
            $showxml->addChild('type', $show['type']);

            // determine show name (timeslot name) for this playlist.
            // TODO this only considers the start of the timeslot... what if playlist overlaps timeslots?
            // best option might be to not allow playlist to overlap timeslots (which might be useful in itself).
            $timeslot = $this->TimeslotsModel('get_permissions', $show['start'], $show['start'] + 1, $this->schedule_player_id);
            // $timeslot = $timeslot[2];
            if (!empty($timeslot)) {
                $showxml->addChild('name', $timeslot[0]['description']);
            }

            $mediaxml = $showxml->addChild('media');
            $voicetrackxml = $showxml->addChild('voicetracks');

            if ($show['item_type'] == 'linein') {
                $media_items = [['type' => 'linein','duration' => $show['duration']]];
            } elseif ($show['item_type'] == 'media') {
                $this->db->where('id', $show['item_id']);
                $media = $this->db->get_one('media');
                $media['item_type'] = $media['type'];
                $media['type'] = 'media';
                if ($media['item_type'] == 'image') {
                    $media['duration'] = $show['duration'];
                }

                if ($media) {
                    if (empty($timeslot)) {
                        $showxml->addChild('name', '');
                    }
                    $showxml->addChild('description', $media['artist'] . ' - ' . $media['title']);
                    $showxml->addChild('last_updated', $media['updated']);

                    $media_items = [$media];
                }
            } elseif ($show['item_type'] == 'playlist') {
                $this->db->where('id', $show['item_id']);
                $playlist = $this->db->get_one('playlists');
                $showxml->addChild('description', $playlist['description']);

                // if we didn't get our show name from the timeslot, then use the playlist name as the show name.
                if (empty($timeslot)) {
                    $showxml->addChild('name', $playlist['name']);
                }

                // add any playlist properties to xml
                $propertiesxml = $showxml->addChild('properties');
                if ($playlist['properties']) {
                    foreach (json_decode($playlist['properties'], true) as $propertyKey => $propertyValue) {
                        $propertiesxml->addChild($propertyKey, $propertyValue);
                    }
                }

                // see if we have selected media in our cache.
                /*
                $this->db->where('schedule_id',$show['id']);
                if(!empty($show['recurring_start']))
                {
                  $this->db->where('mode','recurring');
                  $this->db->where('start',$show['start']);
                }
                else $this->db->where('mode','once');
                $this->db->where('player_id',$this->player->id);
                */
                $this->db->where('show_expanded_id', $show['exp_id']);
                $this->db->where('start', $show['start']);
                $this->db->where('player_id', $this->player->id);

                $cache = $this->db->get_one('shows_cache');

                if ($cache) {
                    $media_items = json_decode($cache['data']);
                    foreach ($media_items as $index => $tmp) {
                        $media_items[$index] = get_object_vars($tmp);
                    } // convert object to assoc. array
                    $showxml->addChild('last_updated', $cache['created']);
                } elseif ($this->cache_player_id != $this->player->id) { // this was set to $this->player->player_id which i'm quite sure was wrong... (fyi in case i broke something).
                    // are we using a parent player for cache?
                    $this->db->where('show_expanded_id', $show['exp_id']);
                    $this->db->where('start', $show['start']);
                    $this->db->where('player_id', $this->cache_player_id);

                    $cache = $this->db->get_one('shows_cache');

                    // we are supposed to use a parent player for cache, but that player doesn't have the cached item yet.
                    if (!$cache) {
                        $media_items = $this->PlaylistsModel('resolve', $playlist['id'], $this->schedule_player_id, $player['parent_player_id'], $show_start, $show['duration']);
                        $cache_created = time();
                        $this->db->insert('shows_cache', [
                        'player_id' => $this->cache_player_id,
                        'show_expanded_id' => $show['exp_id'],
                        'start' => $show['start'],
                        'duration' => $show['duration'],
                        'data' => json_encode($media_items),
                        'created' => $cache_created
                        ]);
                    } else {
                        // oh, we do have cache from parent... let's get media items from it.
                        $media_items = json_decode($cache['data']);
                        foreach ($media_items as $index => $tmp) {
                            $media_items[$index] = get_object_vars($tmp);
                        } // convert object to assoc. array
                    }

                    // now we should really have parent player cache ... copy to our main (child) player.
                    $media_items = $this->convert_station_ids($media_items);

                    $cache_created = time();
                    $showxml->addChild('last_updated', $cache_created);

                    $this->db->insert('shows_cache', [
                    'player_id' => $this->player->id,
                    'show_expanded_id' => $show['exp_id'],
                    'start' => $show['start'],
                    'duration' => $show['duration'],
                    'data' => json_encode($media_items),
                    'created' => $cache_created
                    ]);
                }

                // do we still not have media items for some reason? no cache, no parent cache, or something went wrong...
                if ($media_items === false) {
                    $media_items = $this->PlaylistsModel('resolve', $playlist['id'], $this->schedule_player_id, false, $show_start, $show['duration']);

                    $cache_created = time();
                    $showxml->addChild('last_updated', $cache_created);

                    $this->db->insert('shows_cache', [
                    'player_id' => $this->player->id,
                    'show_expanded_id' => $show['exp_id'],
                    'start' => $show['start'],
                    'duration' => $show['duration'],
                    'data' => json_encode($media_items),
                    'created' => $cache_created
                    ]);
                }
            }

            $order_count = 0;
            $media_offset = 0.0;
            $media_audio_offset = 0.0;
            $media_image_offset = 0.0;

            foreach ($media_items as $media_item) {
                // disallow voicetrack for non-standard playlists
                if ($show['type'] != 'standard' && $media_item['type'] == 'voicetrack') {
                    continue;
                }

                // special handling for voicetrack
                if ($media_item['type'] == 'voicetrack') {
                    $itemxml = $voicetrackxml->addChild('item');
                    $this->media_item_xml($itemxml, $media_item, $order_count, $media_offset);
                    continue;
                }

                if ($show['type'] == 'standard' && $media_offset > $show['duration']) {
                    break;
                }

                if ($show['type'] == 'advanced') {
                    if (max($media_audio_offset, $media_image_offset) > $show['duration']) {
                        break;
                    }

                    if ($media_item['type'] == 'audio') {
                        $media_offset = $media_audio_offset;
                        $media_audio_offset += $media_item['duration'] - ($media_item['crossfade'] ?? 0);
                    } elseif ($media_item['type'] == 'image') {
                        $media_offset = $media_image_offset;
                        $media_image_offset += $media_item['duration'];
                    } else {
                        $media_offset = max($media_audio_offset, $media_image_offset);
                        $media_audio_offset = $media_offset + $media_item['duration'];
                        $media_image_offset = $media_offset + $media_item['duration'];
                    }
                }

                $itemxml = $mediaxml->addChild('item');
                $media_item['context'] = 'show';

                $this->media_item_xml($itemxml, $media_item, $order_count, $media_offset);

                if ($show['type'] == 'standard' || $show['type'] == 'live_assist') {
                    $media_offset += ($media_item['duration'] ?? 0) - ($media_item['crossfade'] ?? 0);
                }

                $order_count++;
            }

            // live assist shows always use specified show duration (total media duration means nothing because of breakpoints)
            if ($show['type'] == 'live_assist') {
                $show_actual_duration = $show['duration'];
            } else {
                $show_actual_duration = $media_offset;
            }

            // if the show-specified duration is less than the actual duration (total of media durations), then use the the show-specified so the next show isn't cut of at the beginning.
            // otherwise, use the actual duration (shorter) so that we can fill in the rest with 'default playlist' material.
            $showxml->addChild('duration', $show['duration'] < $show_actual_duration ? $show['duration'] : $show_actual_duration);

            $show_times[] = ['start' => $show['start'],'end' => $show['start'] + min($show['duration'], $show_actual_duration)];


            if ($show['item_type'] == 'playlist' && $show['type'] == 'live_assist') {
                $this->add_liveassist_buttons($playlist['id'], $show, $showxml);
            }

            // make sure we have media items and if not remove the show
            if ($show['type'] != 'live_assist' && empty($showxml->media)) {
                unset($schedxml->show[count($schedxml->show) - 1]);
            }
        }

        usort($show_times, function ($a, $b) {
            if ($a['start'] == $b['start']) {
                return 0;
            }
            return ($a['start'] < $b['start']) ? -1 : 1;
        });

        // fill in blank spots with default playlist, if we have one.
        if (!empty($this->default_playlist_id)) {
            // default starting time is now. but we'll check to see whether there is an earlier starting time from a cached default playlist which is still playing.
            $timestamp_pointer = time();

            remoteDebug('looking for cached playlist at time ' . gmdate('Y-m-d H:i:s', $timestamp_pointer));

            $this->db->query('SELECT start FROM shows_cache WHERE player_id = ' . $this->db->escape($this->default_playlist_player_id) . ' AND show_expanded_id IS NULL AND start <= ' . $this->db->escape($timestamp_pointer) . ' AND start+duration > ' . $this->db->escape($timestamp_pointer));

            if ($this->db->num_rows() > 1) {
                remoteDebug('found more than one cached playlist (' . $this->db->num_rows() . '), this shouldn\'t happen');
            }
            if ($this->db->num_rows() > 0) {
                $cached_default_playlist = $this->db->assoc_row();
                $timestamp_pointer = $cached_default_playlist['start'];
                remoteDebug('found, updating timestamp pointer to ' . gmdate('Y-m-d H:i:s', $timestamp_pointer));
            } else {
                remoteDebug('not found');
            }

            for ($default_playlist_counter = 0; $timestamp_pointer < $end_timestamp; $default_playlist_counter++) {
                // handling for default playlist during current gap (before first show in the outputted schedule)
                if ($default_playlist_counter == 0 && (count($show_times) == 0 || $show_times[0]['start'] > $timestamp_pointer)) {
                    $default_start = $timestamp_pointer;

                    remoteDebug('FOUND GAP, setting default start to ' . gmdate('Y-m-d H:i:s', $default_start));

                    // if no show times, then we are an indefinite gap
                    // otherwise, gap until the start of the first show
                    if (count($show_times) == 0) {
                        $default_end = $end_timestamp;
                    } else {
                        $default_end = $show_times[0]['start'];
                    }

                    $default_start_tmp = $default_start;

                    // loop to fill with default playlist to fill the gap
                    while ($default_start_tmp < $default_end) {
                        remoteDebug('FILL GAP LOOP 1 with start ' . gmdate('Y-m-d H:i:s', $default_start_tmp) . ' and end ' . gmdate('Y-m-d H:i:s', $default_end));

                        if ($default_start_tmp > $end_timestamp) {
                            break(2);
                        } // end of buffer, we're done.

                        // get show content.
                        $showxml = $schedxml->addChild('show');

                        // add default playlist as a show. (this function returns duration, so we add it to our time).
                        $show_duration = $this->default_playlist_show_xml($showxml, $default_start_tmp, ($default_end - $default_start_tmp));
                        if ($show_duration <= 0) {
                            break(2);
                        } // no show duration, cancel.
                        $default_start_tmp = $default_start_tmp + $show_duration;
                    }
                }

                // handling for default playlist during current gap (between or at the end of shows in the outputted schedule)
                if (!empty($show_times[$default_playlist_counter])) {
                    $default_start = ceil($show_times[$default_playlist_counter]['end']); // need ceiling since we store start times in whole numbers.

                    if (count($show_times) > ($default_playlist_counter + 1)) {
                        $default_end = $show_times[$default_playlist_counter + 1]['start'];
                    } else {
                        $default_end = $end_timestamp;
                    }

                    // this will be false if there is no gap between shows, or at the end where a show goes over our end timestamp.
                    if ($default_start < $default_end) {
                        $default_start_tmp = $default_start;

                        remoteDebug('FILL GAP LOOP 2 with start ' . gmdate('Y-m-d H:i:s', $default_start_tmp) . ' and end ' . gmdate('Y-m-d H:i:s', $default_end));

                        while ($default_start_tmp < $default_end) {
                            if ($default_start_tmp > $end_timestamp) {
                                break(2);
                            } // end of buffer, we're done.

                            // get show content.
                            $showxml = $schedxml->addChild('show');

                            // add default playlist as a show. (this function returns duration, so we add it to our time).
                            $show_duration = $this->default_playlist_show_xml($showxml, $default_start_tmp, ($default_end - $default_start_tmp));
                            if ($show_duration <= 0) {
                                break(2);
                            } // no show duration, cancel.
                            $default_start_tmp = $default_start_tmp + $show_duration;
                        }
                    }
                }

                $timestamp_pointer = $default_end;
            }
        }

        header("content-type: text/xml");
        echo @$this->xml->asXML();

        return true;
    }

    private function add_liveassist_buttons($playlist_id, $show, $show_xml)
    {

        // create start datetime object (used for playlist resolve)
        $show_start = new \DateTime('@' . $show['start'], new \DateTimeZone('UTC'));
        $show_start->setTimezone(new \DateTimeZone($this->player->timezone));

        $buttons_xml = $show_xml->addChild('liveassist_buttons');

        $this->db->where('playlist_id', $playlist_id);
        $this->db->orderby('order_id');
        $buttons = $this->db->get('playlists_liveassist_buttons');

        foreach ($buttons as $button) {
            $this->db->where('id', $button['button_playlist_id']);
            $playlist = $this->db->get_one('playlists');

            if (!$playlist) {
                continue;
            } // playlist not available.

            $this->db->where('player_id', $this->player->id);
            $this->db->where('start', $show['start']);
            $this->db->where('playlists_liveassist_button_id', $button['id']);

            // $cache = $this->db->get_one('schedules_liveassist_buttons_cache');

            /*
            if ($cache) {
                $items = (array) json_decode($cache['data']);
                $cache_created = $cache['created'];
            } else {
            */
                $items = $this->PlaylistsModel('resolve', $button['button_playlist_id'], $this->player->id, false, $show_start);
                $cache_created = time();
                // $showxml->addChild('last_updated',$cache_created);
                // $this->db->insert('schedules_liveassist_buttons_cache', array('player_id' => $this->player->id,'start' => $show['start'],'playlists_liveassist_button_id' => $button['id'],'data' => json_encode($items),'created' => $cache_created));
            // }

            $group_xml = $buttons_xml->addChild('group');
            $group_xml->addChild('last_updated', $cache_created);
            $group_xml->addChild('name', $playlist['name']);
            $media_xml = $group_xml->addChild('media');

            foreach ($items as $item) {
                $item = (array) $item;
                if ($item['type'] == 'breakpoint') {
                    continue;
                }
                $item_xml = $media_xml->addChild('item');
                $this->media_item_xml($item_xml, $item);
            }
        }
    }


    private function convert_station_ids($media_items)
    {
        // no need to swap out station IDs, we're already using parent's ids.
        if ($this->player->use_parent_ids) {
            return $media_items;
        }

        // swap out station IDs from parent, with station IDs for child.
        $new_items = [];
        foreach ($media_items as $index => $item) {
      // this item is a station ID. get a station id from our child player instead.
            if (!empty($item['is_station_id'])) {
                $this->db->query('SELECT media.* FROM players_station_ids LEFT JOIN media ON players_station_ids.media_id = media.id WHERE player_id="' . $this->db->escape($this->player->id) . '" order by rand() limit 1;');
                $rows = $this->db->assoc_list();
                if (count($rows) > 0) {
                    // if this station id is an image, how long should we display it for? check player settings.
                    if ($rows[0]['type'] == 'image') {
                        $rows[0]['duration'] = $this->player->station_id_image_duration;
                    }

                    $rows[0]['is_station_id'] = true;

                    // add to our media items.
                    $new_items[] = $rows[0];
                }
            } else {
                $new_items[] = $item;
            }
        }

        return $new_items;
    }

    private function media_item_xml(&$itemxml, $track, $ord = false, $offset = false)
    {
        // special handling for 'breakpoint' (not really a media item, more of an instruction).
        if ($track['type'] == 'breakpoint') {
            if ($ord !== false) {
                $itemxml->addChild('order', $ord);
            }
            if ($offset !== false) {
                $itemxml->addChild('offset', $offset);
            } // offset is replacing 'order' to allow multiple media to play at once.
            $itemxml->addChild('duration', 0);
            $itemxml->addChild('type', $track['type']);
            return true;
        }

        // get full media metadata
        if ($track['type'] === 'media' || $track['type'] === 'voicetrack') {
            $media = $this->MediaModel('get_by_id', ['id' => $track['id']]);
            if (!$media) {
                return false;
            }
        }

        $itemxml->addChild('duration', $track['duration']);
        $itemxml->addChild('type', ($track['type'] == 'media' || $track['type'] == 'voicetrack') ? $media['type'] : $track['type']);
        if ($ord !== false) {
            $itemxml->addChild('order', $ord);
        }
        if ($offset !== false) {
            $itemxml->addChild('offset', $offset);
        } // offset is replacing 'order' to allow multiple media to play at once.

        if ($track['type'] == 'media' || $track['type'] == 'voicetrack') {
            if (!empty($media['is_archived'])) {
                $filerootdir = OB_MEDIA_ARCHIVE;
            } elseif (!empty($media['is_approved'])) {
                $filerootdir = OB_MEDIA;
            } else {
                $filerootdir = OB_MEDIA_UPLOADS;
            }
            $fullfilepath = $filerootdir . '/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/' . $media['filename'];

            // missing media file
            // TODO should remove entirely
            // if(!file_exists($fullfilepath)) return false;

            $filesize = filesize($fullfilepath);
            $itemxml->addChild('id', $track['id']);
            $itemxml->addChild('filename', htmlspecialchars($media['filename']));
            $itemxml->addChild('title', htmlspecialchars($media['title']));
            $itemxml->addChild('artist', htmlspecialchars($media['artist']));
            $itemxml->addChild('hash', $media['file_hash']);
            $itemxml->addChild('filesize', $filesize);
            $itemxml->addChild('location', $media['file_location']);
            $itemxml->addChild('archived', $media['is_archived']);
            $itemxml->addChild('approved', $media['is_approved']);
            if (isset($media['thumbnail'])) {
                $itemxml->addChild('thumbnail', $media['thumbnail']);
            }
            $itemxml->addChild('context', $track['context']);
            if ($track['crossfade'] ?? null) {
                $itemxml->addChild('crossfade', $track['crossfade']);
            }
        }

        if ($track['type'] == 'voicetrack') {
            $voicetrackxml = $itemxml->addChild('voicetrack');
            $voicetrackxml->addChild('fadeout', $track['voicetrack_fadeout_before']);
            $voicetrackxml->addChild('fadein', $track['voicetrack_fadein_after']);
            $voicetrackxml->addChild('volume', $track['voicetrack_volume']);
            $voicetrackxml->addChild('offset', $track['voicetrack_offset']);
        }

        return true;
    }




    // add default playlist to show xml.
    // max duration considered when adding media items to xml, but not when generating for cache purposes.
    // this is because max_duration might 'extend' in the future (as more buffer requested by remote).
    // returns duration.
    private function default_playlist_show_xml(&$showxml, $start, $max_duration)
    {

        remoteDebug('default_playlist_show_xml called with start ' . gmdate('Y - m - d H:i:s', $start) . ' max duration ' . $max_duration);

        if (empty($this->default_playlist_id)) {
            return 0;
        }

        // create start datetime object (used for playlist resolve)
        $show_start = new \DateTime('@' . $start, new \DateTimeZone('UTC'));
        $show_start->setTimezone(new \DateTimeZone($this->player->timezone));

        // get our playlist name to report as the show name (below)
        $this->db->where('id', $this->default_playlist_id);
        $playlist = $this->db->get_one('playlists');

        if ($playlist['type'] == 'live_assist') {
            $playlist['type'] = 'standard';
        } // live_assist converted to standard for default playlist.

        $show_media_items = [];

        // see if we have selected media in our cache.
        $this->db->where('player_id', $this->player->id);
        $this->db->where('show_expanded_id', null);
        $this->db->where('start', $start);
        // $this->db->where('duration',$end-$start);

        $cache = $this->db->get_one('shows_cache');

        if ($cache) {
            $show_media_items = json_decode($cache['data']);
            foreach ($show_media_items as $index => $tmp) {
                $show_media_items[$index] = get_object_vars($tmp);
            } // convert object to assoc. array
            $showxml->addChild('last_updated', $cache['created']);
            $duration = $cache['duration'];

            remoteDebug('default_playlist_show_xml FOUND CACHED default playlist with duration ' . $duration);
        } elseif ($this->cache_player_id != $this->player->id && $this->player->use_parent_playlist) {
            remoteDebug('default_playlist_show_xml NO FOUND CACHED default playlist, using parent player for cache');

            // are we using a parent player for cache (and playlist)?
            // see if parent has a cache entry.
            $this->db->where('player_id', $this->cache_player_id);
            $this->db->where('show_expanded_id', null);
            $this->db->where('start', $start);
            // $this->db->where('duration',$end-$start);

            $cache = $this->db->get_one('shows_cache');

            // we are supposed to use a parent player for cache, but that player doesn't have the cached item yet .
            if (!$cache) {
                // don't specify max duration for playlist resolve since it's likely we'll need more items on subsequent sync.
                // no max duration means the whole playlist will be rendered.
                $show_media_items = $this->PlaylistsModel('resolve', $this->default_playlist_id, $this->default_playlist_player_id, false, $show_start);
                $cache_created = time();
                $duration = $this->total_items_duration($show_media_items, $playlist['type'] == 'advanced');
                $this->db->insert('shows_cache', [
                'show_expanded_id' => null,
                'player_id' => $this->cache_player_id,
                'start' => $start,
                'duration' => $duration,
                'data' => json_encode($show_media_items),
                'created' => $cache_created
                ]);
            } else {
                // oh, we do have cache from parent... let's get media items from it.
                $show_media_items = json_decode($cache['data']);
                foreach ($show_media_items as $index => $tmp) {
                    $show_media_items[$index] = get_object_vars($tmp);
                } // convert object to assoc. array
            }

            // now we should really have parent player cache ... copy to our main (child) player.
            $show_media_items = $this->convert_station_ids($show_media_items);

            $duration = $this->total_items_duration($show_media_items, $playlist['type'] == 'advanced');

            $cache_created = time();
            $showxml->addChild('last_updated', $cache_created);

            $this->db->insert('shows_cache', [
            'show_expanded_id' => null,
            'player_id' => $this->player->id,
            'start' => $start,
            'duration' => $duration,
            'data' => json_encode($show_media_items),
            'created' => $cache_created
            ]);
        } elseif (!$cache) {
            remoteDebug('default_playlist_show_xml NO FOUND CACHED default playlist');
        }

        // still don't have media items?
        if (empty($show_media_items)) {
            remoteDebug('default_playlist_show_xml NO MEDIA ITEMS default playlist, generating');

            // don't specify max duration for playlist resolve since it's likely we'll need more items on subsequent sync.
            // no max duration means the whole playlist will be rendered.
            $show_media_items = $this->PlaylistsModel('resolve', $this->default_playlist_id, $this->default_playlist_player_id, false, $show_start);

            $duration = $this->total_items_duration($show_media_items, $playlist['type'] == 'advanced');

            $cache_created = time();
            $showxml->addChild('last_updated', $cache_created);

            $this->db->insert('shows_cache', [
                'show_expanded_id' => null,
                'player_id' => $this->player->id,
                'start' => $start,
                'duration' => $duration,
                'data' => json_encode($show_media_items),
                'created' => $cache_created
            ]);

            remoteDebug('default_playlist_show_xml CREATING NEW CACHE default playlist with start ' . gmdate('Y-m-d H:i:s', $start) . ' duration ' . $duration);
        }

        // generate XML for show/media items.
        $showxml->addChild('id', 0);
        $showxml->addChild('date', gmdate('Y-m-d', $start));
        $showxml->addChild('time', gmdate('H:i:s', $start));
        $showxml->addChild('name', $playlist['name']);
        $showxml->addChild('type', $playlist['type']);
        $showxml->addChild('description', 'Default Playlist');
        // $showxml->addChild('last_updated',time());
        $showxml->addChild('duration', min($max_duration, $duration));

        $mediaxml = $showxml->addChild('media');
        $voicetrackxml = $showxml->addChild('voicetracks');

        $order_count = 0;

        $media_offset = 0.0;
        $media_audio_offset = 0.0;
        $media_image_offset = 0.0;

        foreach ($show_media_items as $media_item) {
            // disallow voicetrack for non-standard playlists
            if ($playlist['type'] != 'standard' && $media_item['type'] == 'voicetrack') {
                continue;
            }

            // special handling for voicetrack
            if ($media_item['type'] == 'voicetrack') {
                $itemxml = $voicetrackxml->addChild('item');
                $this->media_item_xml($itemxml, $media_item, $order_count, $media_offset);
                continue;
            }

            if ($media_item['type'] == 'breakpoint') {
                continue;
            } // completely ignore breakpoints. (live assist converted to standard playlist).

            if ($playlist['type'] == 'advanced') {
                if ($media_item['type'] == 'audio') {
                    // if our audio offset is already past the max duration, we don't want to add more audio.
                    if ($media_audio_offset >= $max_duration) {
                        continue;
                    }

                    $media_offset = $media_audio_offset;
                    $media_audio_offset += $media_item['duration'] - ($media_item['crossfade'] ?? 0);
                } elseif ($media_item['type'] == 'image') {
                    // if our image offset is already past the max duration, we don't want to add more images.
                    if ($media_image_offset >= $max_duration) {
                        continue;
                    }

                    $media_offset = $media_image_offset;
                    $media_image_offset += $media_item['duration'];
                } else {
                    // if audio or image offset is already past the max duration, we don't want to add anymore anything!
                    // (adding video would start past the max_duration point).
                    if (max($media_audio_offset, $media_image_offset) >= $max_duration) {
                        break;
                    }

                    $media_offset = max($media_audio_offset, $media_image_offset);
                    $media_audio_offset = $media_offset + $media_item['duration'];
                    $media_image_offset = $media_offset + $media_item['duration'];
                }
            }

            $itemxml = $mediaxml->addChild('item');
            $this->media_item_xml($itemxml, $media_item, $order_count, $media_offset);

            if ($playlist['type'] == 'standard') {
                $media_offset += $media_item['duration'] - ($media_item['crossfade'] ?? 0);
                if ($media_offset > $max_duration) {
                    break;
                } // our next media offset is beyond max_duration, no more items to add.
            }

            // voicetrack are on a separate layer so don't affect order count
            $order_count++;
        }

        remoteDebug('default_playlist_show_xml finished with max_duration ' . $max_duration . ' duration ' . $duration);

        return min($max_duration, $duration);
    }


    private function total_items_duration($media_items, $advanced = false)
    {
        $media_offset = 0.0;
        $media_audio_offset = 0.0;
        $media_image_offset = 0.0;

        if ($advanced) {
            foreach ($media_items as $media_item) {
                if ($media_item['type'] == 'audio') {
                    $media_audio_offset += $media_item['duration'] - ($media_item['crossfade'] ?? 0);
                } elseif ($media_item['type'] == 'image') {
                    $media_image_offset += $media_item['duration'];
                } else {
                    $media_offset = max($media_audio_offset, $media_image_offset);
                    $media_audio_offset = $media_offset + $media_item['duration'];
                    $media_image_offset = $media_offset + $media_item['duration'];
                }
            }

            return ceil(max($media_audio_offset, $media_image_offset));
        } else {
            foreach ($media_items as $media_item) {
                $media_offset += $media_item['duration'] - ($media_item['crossfade'] ?? 0);
            }
            return ceil($media_offset);
        }
    }

    private function loadRelatedPlayers()
    {
        if ($this->player->parent_player_id) {
            $this->db->where('id', $this->player->parent_player_id);
            $this->parent_player = $this->db->get_one('players');
        }

        if ($this->player->parent_player_id && $this->player->use_parent_playlist) {
            $this->default_playlist_id = $this->parent_player['default_playlist_id'];
            $this->default_playlist_player_id = $this->player->parent_player_id;
        } else {
            $this->default_playlist_id = $this->player->default_playlist_id;
            $this->default_playlist_player_id = $this->player->id;
        }

        if ($this->player->parent_player_id && $this->player->use_parent_schedule) {
            $this->schedule_player_id = $this->player->parent_player_id;
        } else {
            $this->schedule_player_id = $this->player->id;
        }

        if ($this->player->parent_player_id && $this->player->use_parent_schedule && $this->player->use_parent_dynamic) {
            $this->cache_player_id = $this->player->parent_player_id;
        } else {
            $this->cache_player_id = $this->player->id;
        }
    }
}
