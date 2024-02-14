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
 * Manages playlist data, dynamic selections, their permissions, and LiveAssist
 * items.
 *
 * @package Model
 */
class PlaylistsModel extends OBFModel
{
    /**
     * Insert a playlist.
     *
     * @param data
     */
    public function insert($data)
    {
        return $this->db->insert('playlists', $data);
    }

    /**
     * Update a playlist.
     *
     * @param data
     */
    public function update($data)
    {
        return $this->db->update('playlists', $data);
    }

    /**
     * Update the users who have permissions for a specified playlist.
     *
     * @param playlist_id
     * @param user_ids
     */
    public function update_permissions_users($playlist_id, $user_ids)
    {
        if (!is_array($user_ids)) {
            return false;
        }

        $this->db->where('playlist_id', $playlist_id);
        $this->db->delete('playlists_permissions_users');

        foreach ($user_ids as $user_id) {
            $this->db->insert('playlists_permissions_users', ['playlist_id' => $playlist_id, 'user_id' => $user_id]);
        }

        return true;
    }

    /**
     * Update the groups who have permissions for a specified playlist.
     *
     * @param playlist_id
     * @param group_ids
     */
    public function update_permissions_groups($playlist_id, $group_ids)
    {
        if (!is_array($group_ids)) {
            return false;
        }

        $this->db->where('playlist_id', $playlist_id);
        $this->db->delete('playlists_permissions_groups');

        foreach ($group_ids as $group_id) {
            $this->db->insert('playlists_permissions_groups', ['playlist_id' => $playlist_id, 'group_id' => $group_id]);
        }

        return true;
    }

    /**
     * Get the user and group permissions for a specified playlist.
     *
     * @param playlist_id
     *
     * @return [groups, users]
     */
    public function get_permissions($playlist_id)
    {
        $return = [];
        $return['groups'] = [];
        $return['users'] = [];

        $this->db->where('playlist_id', $playlist_id);
        $groups = $this->db->get('playlists_permissions_groups');
        foreach ($groups as $group) {
            $return['groups'][] = (int) $group['group_id'];
        }

        $this->db->where('playlist_id', $playlist_id);
        $users = $this->db->get('playlists_permissions_users');
        foreach ($users as $user) {
            $return['users'][] = (int) $user['user_id'];
        }

        return $return;
    }

    /**
     * Get a playlist.
     *
     * @param id
     *
     * @return playlist
     */
    public function get_by_id($id)
    {
        $this->db->what('playlists.*');
        $this->db->what('users.display_name', 'owner_name');
        $this->db->where('playlists.id', $id);
        $this->db->leftjoin('users', 'playlists.owner_id', 'users.id');

        $playlist = $this->db->get_one('playlists');

        return $playlist;
    }

    /**
     * Get detailed media items for a playlist ID.
     *
     * @param id
     *
     * @return playlist_items
     */
    public function get_items($id)
    {
        $return = array();

        // get playlist items.
        $this->db->orderby('playlists_items.ord');

        $this->db->where('playlist_id', $id);
        $this->db->what('playlists_items.ord', 'ord');
        $this->db->what('playlists_items.item_type', 'type');

        $this->db->what('playlists_items.properties', 'properties');

        $this->db->what('media.id', 'id');
        $this->db->what('media.type', 'media_type');
        $this->db->what('media.title', 'title');
        $this->db->what('media.artist', 'artist');
        $this->db->what('media.owner_id', 'owner_id');
        $this->db->what('media.status', 'status');
        $this->db->what('media.duration', 'duration');

        $this->db->leftjoin('media', 'playlists_items.item_id', 'media.id');

        $items = $this->db->get('playlists_items');

        if ($items) {
            foreach ($items as $item) {
                // decode properties if we have them
                if ($item['properties']) {
                    $item['properties'] = json_decode($item['properties'], true);
                }

                // for media type, provide 'audio' 'video' or 'image' instead.
                if ($item['type'] == 'media') {
                    $item['type'] = $item['media_type'];
                }

                if ($item['type'] == 'image') {
                    $item['duration'] = $item['properties']['duration'];
                }

                // we don't use this (merged into 'type')
                unset($item['media_type']);

                // for dynamic items, provide a time estimate.
                if ($item['type'] == 'dynamic') {
                    $item['duration'] = $this('dynamic_selection_duration', $item['properties']['query'], $item['properties']['num_items'], $item['properties']['image_duration']);
                }

                $return[] = $item;
            }
        }

        return $return;
    }

    /**
     * Get LiveAssist buttons associated with a playlist.
     *
     * @param id
     *
     * @return liveassist_buttons
     */
    public function get_liveassist_items($id)
    {
        $this->db->what('playlists.id', 'id');
        $this->db->what('playlists.name', 'name');
        $this->db->what('playlists.description', 'description');

        $this->db->orderby('playlists_liveassist_buttons.order_id');
        $this->db->where('playlists_liveassist_buttons.playlist_id', $id);
        $this->db->leftjoin('playlists', 'playlists_liveassist_buttons.button_playlist_id', 'playlists.id');
        return $this->db->get('playlists_liveassist_buttons');
    }

    /**
     * Provide information about where a playlist is used. This includes shows,
     * default playlists, and LiveAssist buttons.
     *
     * @param id
     *
     * @return [used, id, can_delate]
     */
    public function where_used($id)
    {
        $info = array();

        $info['used'] = array();
        $info['id'] = $id;
        $info['can_delete'] = true;

        // is this used on a show?
        $this->db->what('players.id', 'player_id');
        $this->db->what('players.name', 'player_name');
        $this->db->what('shows.user_id', 'user_id');
        $this->db->what('shows.id', 'id');
        $this->db->where('item_id', $id);
        $this->db->where('item_type', 'playlist');
        $this->db->leftjoin('players', 'shows.player_id', 'players.id');
        $shows = $this->db->get('shows');

        foreach ($shows as $show) {
            if ($show['user_id'] != $this->user->param('id') && !$this->user->check_permission('manage_timeslots')) {
                $info['can_delete'] = false;
            }

            $used_data = new stdClass();
            $used_data->where = 'show';
            $used_data->name = $show['player_name']; // for player
            $used_data->id = $show['id'];
            $used_data->user_id = $show['user_id'];

            $info['used'][] = $used_data;
        }

        // is this used as a default playlist?
        $this->db->what('id', 'player_id');
        $this->db->what('name', 'player_name');
        $this->db->where('default_playlist_id', $id);
        $players = $this->db->get('players');

        foreach ($players as $player) {
            $used_data = new stdClass();
            $used_data->where = 'default playlist';
            $used_data->name = $player['player_name']; // for player
            $used_data->id = $player['player_id'];
            $used_data->user_id = false;

            $info['used'][] = $used_data;
        }

        // is this used as a liveassist button playlist?
        $this->db->where('button_playlist_id', $id);
        $this->db->leftjoin('playlists', 'playlists_liveassist_buttons.playlist_id', 'playlists.id');
        $liveassist_playlists = $this->db->get('playlists_liveassist_buttons');

        foreach ($liveassist_playlists as $playlist) {
            $used_data = new stdClass();
            $used_data->where = 'liveassist buttons';
            $used_data->name = $playlist['name']; // for playlist
            $used_data->id = $playlist['id'];
            $used_data->user_id = false;

            $info['used'][] = $used_data;
        }

        return $info;
    }

    /**
     * Search for playlists.
     *
     * @param query,
     * @param limit
     * @param offset
     * @param sort_by
     * @param sort_dir
     * @param my Limit results to currently logged in user. Default FALSE.
     *
     * @return [num_results, playlists]
     */
    public function search($query, $limit, $offset, $sort_by, $sort_dir, $my = false)
    {
        $where_strings = array();

        if ($query !== '' && $query !== false && $query !== null) {
            $where_strings[] = '(name LIKE "%' . $this->db->escape($query) . '%" OR description LIKE "%' . $this->db->escape($query) . '%")';
        }
        if (!$this->user->check_permission('manage_playlists')) {
            $where_strings[] = '(status = "public" or status = "visible" or owner_id = "' . $this->db->escape($this->user->param('id')) . '")';
        }

        // limit results to those owned by the presently logged in user.
        if ($my) {
            $where_strings[] = 'owner_id = "' . $this->db->escape($this->user->param('id')) . '"';
        }

        if (count($where_strings) > 0) {
            $this->db->where_string(implode(' AND ', $where_strings));
        }

        if (!empty($offset)) {
            $this->db->offset($offset);
        }
        if (!empty($limit)) {
            $this->db->limit($limit);
        }

        // otherwise, if posted sort by data is valid, use that...
        if (($sort_dir == 'asc' || $sort_dir == 'desc') && array_search($sort_by, array('name','description','updated')) !== false) {
            $this->db->orderby($sort_by, $sort_dir);
        } else {
            // otherwise, show the most recently updated first
            $this->db->orderby('updated', 'desc');
        }

        $this->db->calc_found_rows();

        $playlists = $this->db->get('playlists');

        return array('num_results' => $this->db->found_rows(),'playlists' => $playlists);
    }

    /**
     * Validate a playlist.
     *
     * @param data
     *
     * @return is_valid
     */
    public function validate_playlist($data)
    {

    //T A playlist name is required.
        if (empty($data['name'])) {
            return array(false,'A playlist name is required.');
        }
        //T A valid status is required.
        if ($data['status'] != 'private' && $data['status'] != 'visible' && $data['status'] != 'public') {
            return array(false,'A valid status is required.');
        }
        //T A valid type is required.
        if ($data['type'] != 'standard' && $data['type'] != 'advanced' && $data['type'] != 'live_assist') {
            return array(false,'A valid type is required.');
        }

        return array(true,'Playlist is valid.');
    }

    /**
     * Validate a single playlist item.
     *
     * @param item
     * @param playlist_id Set if validating an existing playlist. Default NULL.
     *
     * @return is_valid
     */
    public function validate_playlist_item($item, $playlist_id = null)
    {
        if ($playlist_id) {
            $original_playlist = $this('get_by_id', $playlist_id);
        }

        //T One or more playlist items are not valid.
        if ($item['type'] != 'media' && $item['type'] != 'dynamic' && $item['type'] != 'station_id' && $item['type'] != 'breakpoint' && $item['type'] != 'custom') {
            return array(false,'One or more playlist items are not valid.');
        }

        if ($item['type'] == 'media') {
      //T One or more media durations are invalid or zero.
            if (empty($item['duration']) || !preg_match('/^[0-9]+(\.[0-9]+)?$/', $item['duration']) || $item['duration'] <= 0) {
                return array(false,'One or more media durations are invalid or zero.');
            }

            $this->db->where('id', $item['id']);
            $media = $this->db->get_one('media');

            //T One or more playlist items are not valid.
            if (!$media) {
                return array(false,'One or more playlist items are not valid.');
            }

            //T Only approved, unarchived media can be used in playlists.
            if ($media['is_approved'] == 0 || $media['is_archived'] == 1) {
                return array(false,'Only approved, unarchived media can be used in playlists.');
            }

            // can't use private media that isn't ours unless we have 'manage_media' permission.
            if ($media['status'] == 'private' && $media['owner_id'] != $this->user->param('id')) {
                $this->user->require_permission('manage_media');
            }

            // can't add private media to a playlist with a different owner.
            //T A media item is marked as private. It can only be used in playlists created by the same owner.
            if (!$playlist_id && $media['status'] == 'private' && $media['owner_id'] != $this->user->param('id')) {
                return array(false,'A media item is marked as private. It can only be used in playlists created by the same owner.');
            }
            //T A media item is marked as private. It can only be used in playlists created by the same owner.
            if ($playlist_id && $media['status'] == 'private' && $media['owner_id'] != $original_playlist['owner_id']) {
                return array(false,'A media item is marked as private. It can only be used in playlists created by the same owner.');
            }
        } elseif ($item['type'] == 'dynamic') {
            $dynamic_validation = $this('validate_dynamic_properties', json_decode($item['query']), $item['num_items'], $item['num_items_all'], $item['image_duration']);
            //T One or more dynamic playlist items are not valid.
            if ($dynamic_validation[0] == false) {
                return array(false,'One or more dynamic playlist items are not valid.');
            }
        } elseif ($item['type'] == 'custom') {
            $custom_name = $item['query']['name'] ?? '';
            $this->db->where('name', $custom_name);
            //T One or more custom playlist items are not valid.
            if (!$this->db->get_one('playlists_items_types')) {
                return array(false,'One or more custom playlist items are not valid.');
            }
        }

        return array(true,'Playlist item is valid.');
    }

    /**
     * Validate that a LiveAssist button's playlist ID exists.
     *
     * @param playlist_id
     *
     * @return [exists, msg]
     */
    public function validate_liveassist_button_item($playlist_id)
    {
        if ($this->db->id_exists('playlists', $playlist_id)) {
            return array(true,'Live Assist button item is valid.');
        } else {
            //T One or more Live Assist button playlists are invalid.
            return array(false,'One or more Live Assist button playlists are invalid.');
        }
    }

    /**
     * Validate dynamic properties.
     *
     * @param search_query
     * @param num_items
     * @param num_items_all Boolean set to TRUE to use all items. Overrides num_items.
     * @param image_duration
     *
     * @return [is_valid, msg]
     */
    public function validate_dynamic_properties($search_query, $num_items, $num_items_all, $image_duration)
    {
        $search_query = (array) $search_query; // convert to array (maybe comes in as object?)

        //T The number of items is invalid.
        if (!$num_items_all && (!preg_match('/^[0-9]+$/', $num_items) || $num_items == '0')) {
            return array(false,'The number of items is invalid.');
        }
        //T The image duration is invalid.
        if (!preg_match('/^[0-9]+$/', $image_duration) || $image_duration == '0') {
            return array(false,'The image duration is invalid.');
        }

        if ($search_query['mode'] == 'advanced') {
            foreach ($search_query['filters'] as $filter) {
                $filter = (array) $filter;

                // make sure our search field and comparison operator is valid
                // TODO fix code duplication with media model
                $allowed_filters = ['comments','artist','title','album','year','type','category','country','language','genre','duration','is_copyright_owner'];
                $metadata_fields = $this->models->mediametadata('get_all');
                foreach ($metadata_fields as $metadata_field) {
                    $allowed_filters[] = 'metadata_' . $metadata_field['name'];
                }

                //T Invalid search criteria.
                if (array_search($filter['filter'], $allowed_filters) === false) {
                    return array(false,'Invalid search criteria.');
                }

                //T Invalid search criteria.
                if (array_search($filter['op'], array('like','not_like','is','not','gte','lte')) === false) {
                    return array(false,'Invalid search criteria.');
                }
            }
        }

        return array(true,'Dynamic selection is valid.');
    }

    /**
     * Update the LiveAssist buttons using a specific playlist.
     *
     * @param playlist_id
     * @param items
     */
    public function update_liveassist_items($playlist_id, $items)
    {
        $this->db->where('playlist_id', $playlist_id);
        $this->db->delete('playlists_liveassist_buttons');

        $ord = 0;

        foreach ($items as $item) {
            $data = array();
            $data['playlist_id'] = $playlist_id;
            $data['order_id'] = $ord;
            $data['button_playlist_id'] = $item;

            $this->db->insert('playlists_liveassist_buttons', $data);

            $ord++;
        }

        return true;
    }

    /**
     * Figure out the dynamic selection duration. An estimate unless all items are
     * selected.
     *
     * @param search_query
     * @param num_items
     * @param image_duration
     *
     * @return duration
     */
    public function dynamic_selection_duration($search_query, $num_items, $image_duration)
    {
        $search_query = (array) $search_query; // convert to array (might come in as object with json_decode)

        $where = array();

        // simple mode
        if ($search_query['mode'] == 'simple') {
            $where[] = '(artist like "%' . $this->db->escape($search_query['string']) . '%" or title like "%' . $this->db->escape($search_query['string']) . '%") and is_approved = 1 and is_archived = 0';
        } else {
            // advanced mode.  Filters should be already validated!
            $filters = $search_query['filters'];

            foreach ($filters as $filter) {
                $filter = (array) $filter;

                // TODO fix code duplication with media model

                // our possible column (mappings)
                $column_array = array();
                $column_array['artist'] = 'media.artist';
                $column_array['title'] = 'media.title';
                $column_array['album'] = 'media.album';
                $column_array['year'] = 'media.year';
                $column_array['type'] = 'media.type';
                $column_array['category'] = 'media.category_id';
                $column_array['country'] = 'media.country_id';
                $column_array['language'] = 'media.language_id';
                $column_array['genre'] = 'media.genre_id';
                $column_array['duration'] = 'media.duration';
                $column_array['comments'] = 'media.comments';

                $metadata_fields = $this->models->mediametadata('get_all');
                foreach ($metadata_fields as $metadata_field) {
                    $column_array['metadata_' . $metadata_field['name']] = 'media_metadata.' . $metadata_field['name'];
                }

                // our possibile comparison operators
                $op_array = array();
                $op_array['like'] = 'LIKE';
                $op_array['not_like'] = 'NOT LIKE';
                $op_array['is'] = '=';
                $op_array['not'] = '!=';
                $op_array['gte'] = '>=';
                $op_array['lte'] = '<=';

                // put together our query segment
                $tmp_sql = $column_array[$filter['filter']] . ' ' . $op_array[$filter['op']] . ' "';

                if ($filter['op'] == 'like' || $filter['op'] == 'not_like') {
                    $tmp_sql .= '%';
                }

                $tmp_sql .= $this->db->escape($filter['val']);

                if ($filter['op'] == 'like' || $filter['op'] == 'not_like') {
                    $tmp_sql .= '%';
                }

                $tmp_sql .= '"';

                $where[] = $tmp_sql;
            }
        }

        // 'all items' selected.
        if (empty($num_items)) {
            $this->db->query('select sum(if(duration is null, ' . $image_duration . ', duration)) as total from media left join media_metadata on media.id=media_metadata.media_id where ' . implode(' AND ', $where));
            $result = $this->db->assoc_list();

            if (empty($result[0]['total'])) {
                return 0;
            }
            return $result[0]['total'];
        } else {
            // complete
            $this->db->query('select avg(if(duration is null, ' . $image_duration . ', duration)) as avg from media left join media_metadata on media.id=media_metadata.media_id where ' . implode(' AND ', $where));
            $result = $this->db->assoc_list();

            if (empty($result[0]['avg'])) {
                return 0;
            }

            return $result[0]['avg'] * $num_items;
        }
    }

    /**
     * Delete playlist, associated items and shows.
     *
     * @param id
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete('playlists');

        return false;
    }

    /**
     * Delete playlist items associated with playlist.
     *
     * @param playlist_id
     */
    public function delete_items($playlist_id)
    {
        $this->db->where('playlist_id', $playlist_id);
        $this->db->delete('playlists_items');
    }

    // item types
    public function get_item_types()
    {
        $this->db->what('name');
        $this->db->what('description');
        $this->db->what('duration');
        $this->db->what('id');
        return $this->db->get('playlists_items_types');
    }

    /**
     * Resolve playlist to create a set of media items only from variable/dynamic data.
     *
     * @param playlist_id
     * @param player_id
     * @param parent_player_id
     * @param start_time Datetime object
     */
    public function resolve($playlist_id, $player_id, $parent_player_id = false, $start_time = null, $max_duration = null)
    {
        // get main player
        $player = $this->models->players('get_one', $player_id);

        // get playlist. max_duration currently supported by standard playlist only. (TODO)
        $playlist = $this('get_by_id', $playlist_id);
        if ($playlist['type'] != 'standard') {
            $max_duration = null;
        }

        // get parent player
        if ($parent_player_id) {
            $parent_player = $this->models->players('get_one', $parent_player_id);
        }

        // figure out which media IDs to exclude based on dayparting
        $dayparting_exclude_ids = $start_time ? $this->models->dayparting('excluded_media_ids', ['start_time' => $start_time]) : [];

        // get playlist items
        $this->db->where('playlist_id', $playlist_id);
        $this->db->orderby('ord');
        $playlist_items = $this->db->get('playlists_items');

        // track items to return
        $return = [];

        // track offset for max duration
        $media_offset = 0.0;

        foreach ($playlist_items as $playlist_item) {
            if ($playlist_item['properties']) {
                $playlist_item['properties'] = json_decode($playlist_item['properties'], true);
            }

            // keep track of media items in this loop only (needed so we can set image duration at end of iteration)
            $media_items_tmp = [];

            // single media item
            if ($playlist_item['item_type'] == 'media') {
                $media = $this->models->media('get_by_id', ['id' => $playlist_item['item_id']]);
                if ($media) {
                    $tmp = ['type' => 'media','id' => $playlist_item['item_id']];
                    if ($media['type'] == 'image') {
                        $tmp['duration'] = $playlist_item['properties']['duration'];
                    } else {
                        $tmp['duration'] = $media['duration'];
                    }
                    $media_offset += $tmp['duration'];
                    if ($media['type'] == 'audio' && isset($playlist_item['properties']['crossfade']) && $playlist_item['properties']['crossfade']) {
                        $tmp['crossfade'] = $playlist_item['properties']['crossfade'];
                    }
                    $tmp['media_type'] = $media['type'];
                    $tmp['context'] = 'Media';
                    if (isset($playlist_item['properties']['voicetrack'])) {
                        $voicetrack = $this->models->media('get_by_id', ['id' => $playlist_item['properties']['voicetrack']]);
                        if ($voicetrack) {
                            $tmp['voicetrack'] = [
                                'id'             => $voicetrack['id'],
                                'duration'       => $voicetrack['duration'],
                                'volume'         => $playlist_item['properties']['voicetrack_volume'] ?? 0.0,
                                'offset'         => $playlist_item['properties']['voicetrack_offset'] ?? 0.0,
                                'fadeout-before' => $playlist_item['properties']['voicetrack_fadeout_before'] ?? 0.0,
                                'fadein-after'   => $playlist_item['properties']['voicetrack_fadein_after'] ?? 0.0,
                            ];
                        }
                    }
                    $media_items_tmp[] = $tmp;
                }
            } elseif ($playlist_item['item_type'] == 'dynamic') {
                // dynamic item
                $dynamic_items = [];

                // get a list of possible items with this query
                $media_search = $this->models->media('search', ['params' => ['query' => $playlist_item['properties']['query']], 'player_id' => $player_id]);
                $media_items = $media_search[0] ?? [];

                // remove dayparting exclusions
                foreach ($media_items as $index => $media_item) {
                    if (array_search($media_item['id'], $dayparting_exclude_ids) !== false) {
                        unset($media_items[$index]);
                    }
                }

                if (!empty($media_items)) {
                    // we keep searching until we have enough items.  this allows randomization, but will not have two of the same tracks playing nearby each other.
                    if ($playlist_item['properties']['num_items']) {
                        while (count($dynamic_items) < $playlist_item['properties']['num_items']) {
                            // randomize our items
                            shuffle($media_items);

                            foreach ($media_items as $media) {
                                $tmp = ['type' => 'media','id' => $media['id']];
                                if ($media['type'] == 'image') {
                                    $tmp['duration'] = $playlist_item['properties']['duration'];
                                } else {
                                    $tmp['duration'] = $media['duration'];
                                }
                                $media_offset += $tmp['duration'];
                                $tmp['media_type'] = $media['type'];
                                $tmp['context'] = 'Dynamic Selection: ' . $playlist_item['properties']['name'];
                                $dynamic_items[] = $tmp;

                                // end loop if we have enough items
                                if (count($dynamic_items) >= $playlist_item['properties']['num_items']) {
                                    break;
                                }
                            }
                        }
                    } else {
                        // randomize our items
                        shuffle($media_items);

                        foreach ($media_items as $media) {
                            $tmp = ['type' => 'media','id' => $media['id']];
                            if ($media['type'] == 'image') {
                                $tmp['duration'] = $playlist_item['properties']['duration'];
                            } else {
                                $tmp['duration'] = $media['duration'];
                            }
                            $media_offset += $tmp['duration'];
                            $tmp['media_type'] = $media['type'];
                            $dynamic_items[] = $tmp;
                        }
                    }
                }

                // add crossfade
                foreach ($dynamic_items as $index => &$item) {
                    // not last item
                    if ($index < count($dynamic_items) - 1) {
                        if ($item['media_type'] == 'audio' && ($playlist_item['properties']['crossfade'] ?? 0)) {
                            $item['crossfade'] = $playlist_item['properties']['crossfade'];
                            $media_offset -= $playlist_item['properties']['crossfade'];
                        }
                    } else {
                        // last item
                        if ($item['media_type'] == 'audio' && ($playlist_item['properties']['crossfade_last'] ?? 0)) {
                            $item['crossfade'] = $playlist_item['properties']['crossfade_last'];
                            $media_offset -= $playlist_item['properties']['crossfade_last'];
                        }
                    }
                }

                $media_items_tmp = array_merge($media_items_tmp, $dynamic_items);
            } elseif ($playlist_item['item_type'] == 'station_id') {
                // random station id
                if ($player['parent_player_id'] && ($generate_for_parent || $player['use_parent_ids'])) {
                    $station_id_player = $player['parent_player_id'];
                    $station_id_image_duration = $parent_player['station_id_image_duration'];
                } else {
                    $station_id_player = $player['id'];
                    $station_id_image_duration = $player['station_id_image_duration'];
                }

                $this->db->query('SELECT media.* FROM players_station_ids LEFT JOIN media ON players_station_ids.media_id = media.id WHERE player_id="' . $this->db->escape($station_id_player) . '";');
                $media_items = $this->db->assoc_list();

                // remove dayparting exclusions
                foreach ($media_items as $index => $media) {
                    if (array_search($media['id'], $dayparting_exclude_ids) !== false) {
                        unset($media_items[$index]);
                    }
                }

                // randomize our selection
                shuffle($media_items);

                if (count($media_items) > 0) {
                    // if this station id is an image, how long should we display it for? check player settings.
                    if ($media_items[0]['type'] == 'image') {
                        $media_items[0]['duration'] = $station_id_image_duration;
                    }

                    $media = $media_items[0];
                    $tmp = ['type' => 'media','id' => $media['id']];
                    if ($media['type'] == 'image') {
                        $tmp['duration'] = $station_id_image_duration;
                    } else {
                        $tmp['duration'] = $media['duration'];
                    }
                    $media_offset += $tmp['duration'];
                    $tmp['is_station_id'] = true;
                    $tmp['media_type'] = $media['type'];
                    $tmp['context'] = 'Station ID';
                    $media_items_tmp[] = $tmp;
                }
            } elseif ($playlist_item['item_type'] == 'breakpoint') {
                $media_items_tmp[] = ['type' => 'breakpoint'];
            } elseif ($playlist_item['item_type'] == 'custom') {
                // get the callback model/method in order for this custom item, add media items specified by the callback method.
                $custom_item_query = $playlist_item['properties'];
                $custom_item_name = $custom_item_query['name'] ?? '';
                $this->db->where('name', $custom_item_name);
                $custom_item_type = $this->db->get_one('playlists_items_types');
                if ($custom_item_type) {
                    $custom_item_type_model = $this->load->model($custom_item_type['callback_model']);
                    if ($custom_item_type_model) {
                        $custom_items = $custom_item_type_model($custom_item_type['callback_method']);
                        if (!is_array($custom_items)) {
                            $custom_items = [$custom_items];
                        }
                        foreach ($custom_items as $custom_items_id) {
                            $this->db->where('id', $custom_items_id);
                            if ($media = $this->db->get_one('media')) {
                                $tmp = ['type' => 'media','id' => $media['id']];
                                if ($media['type'] == 'image') {
                                    $tmp['duration'] = $custom_item_type['duration'];
                                } else {
                                    $tmp['duration'] = $media['duration'];
                                }
                                $media_offset += $tmp['duration'];
                                $tmp['media_type'] = $media['type'];
                                $tmp['context'] = 'Custom';
                                $media_items_tmp[] = $tmp;
                            }
                        }
                    }
                }
            }

            // add our media items from this run to our complete set of media items.
            $return = array_merge($return, $media_items_tmp);

            // break out of loop if we've met our max duration
            if ($max_duration && $media_offset >= $max_duration) {
                break;
            }
        }

        // remove or limit crossfade as required
        foreach ($return as $index => &$item) {
            // skip if crossfade not set
            if ($item['type'] != 'media' || !isset($item['crossfade'])) {
                continue;
            }

            // limit crossfade to item/next-item duration
            if (!isset($return[$index + 1])) {
                $max_crossfade = $item['duration'];
            } else {
                $max_crossfade = min($item['duration'], $return[$index + 1]['duration']);
            }
            if ($item['crossfade'] > $max_crossfade) {
                $item['crossfade'] = $max_crossfade;
            }

            // remove if last track or next track not audio
            if ($index == count($return) - 1 || $return[$index + 1]['media_type'] != 'audio') {
                unset($item['crossfade']);
            }
        }

        // unset temporary data
        foreach ($return as $index => &$item) {
            if (isset($item['media_type'])) {
                unset($item['media_type']);
            }
        }

        return $return;
    }
}
