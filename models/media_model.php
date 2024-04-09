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

/**
 * Manages general media data, not including various types of metadata (which have
 * their own models).
 *
 * @package Model
 */
class MediaModel extends OBFModel
{
    /**
     * Return info about uploaded file.
     *
     * Originally in upload.php, moved here so it can be used elsewhere.
     *
     * @param filename
     *
     * @return [type, duration, format]
     */
    public function media_info($args = [])
    {
        OBFHelpers::require_args($args, ['filename']);

        // this is the info we want -- if we can't get it, it will remain null.
        $return = array();
        $return['type'] = null;
        $return['duration'] = null;
        $return['format'] = null;

        // get our mime data
        if (defined('OB_MAGIC_FILE')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE, OB_MAGIC_FILE);
            $mime = strtolower($finfo->file($args['filename']));
        }

        // did ob_magic_file cause problems?
        if (!defined('OB_MAGIC_FILE') || $mime == '' || $mime == 'application/octet-stream') {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = strtolower($finfo->file($args['filename']));
        }

        $mime_array = explode('/', $mime);

        if ($mime_array[0] == 'image') {
            $return['type'] = 'image';
            if ($mime_array[1] == 'jpeg') {
                $return['format'] = 'jpg';
            } elseif ($mime_array[1] == 'png') {
                $return['format'] = 'png';
            } elseif ($mime_array[1] == 'svg+xml') {
                $return['format'] = 'svg';
            }
        } else {
            // if we have an audio or a video, then we use avprobe to find format and duration.
            $mediainfo_json = shell_exec('ffprobe -show_format -show_streams -of json ' . escapeshellarg($args['filename']));
            if ($mediainfo_json === null || !$mediainfo = json_decode($mediainfo_json)) {
                return $return;
            }

            // if it's a webm file, it's probably headerless on account of MediaRecorder not including these by
            // default when uploading a blob (a problem on Chrome in particular), so we need to add them back in.
            // this can be done by copying the file with ffmpeg, which automatically adds the headers.
            if ($mediainfo->format->format_name == 'matroska,webm') {
                shell_exec('mv ' . escapeshellarg($args['filename']) . ' ' . escapeshellarg($args['filename']) . 'webm');
                shell_exec('ffmpeg -i ' . escapeshellarg($args['filename']) . 'webm -vcodec copy -acodec copy ' . escapeshellarg($args['filename']) . '-header.webm');
                shell_exec('mv ' . escapeshellarg($args['filename']) . '-header.webm ' . escapeshellarg($args['filename']));
                shell_exec('rm ' . escapeshellarg($args['filename']) . 'webm');

                $mediainfo = json_decode(shell_exec('ffprobe -show_format -show_streams -of json ' . escapeshellarg($args['filename'])));
                $mediainfo->format->format_name = 'webm';
            }

            // if missing information or duration is zero, not valid.
            if (empty($mediainfo->streams) || empty($mediainfo->format) || empty($mediainfo->format->duration)) {
                return $return;
            }

            // use avconv to test whether this file is properly readable as audio/video (valid, not encrypted, etc.)
            // TODO more helpful error needed.
            if (OB_MEDIA_VERIFY_CMD) {
                $avconv_return_var = null;
                $avconv_output = [];

                $strtr_array = array('{infile}' => escapeshellarg($args['filename']));
                exec(strtr(OB_MEDIA_VERIFY_CMD, $strtr_array), $avconv_output, $avconv_return_var);
                if ($avconv_return_var != 0) {
                    return $return;
                }
            }

            $has_video_stream = false;
            $has_audio_stream = false;

            $possibly_audio = array_search($mediainfo->format->format_name, array('flac','mp3','ogg','webm','wav')) !== false || $mediainfo->format->format_long_name == 'QuickTime / MOV';

            foreach ($mediainfo->streams as $stream) {
                if (!isset($stream->codec_name) || !isset($stream->codec_type)) {
                    continue;
                }

                // ignore probable cover art
                if ($possibly_audio && ($stream->codec_name == 'mjpeg' || $stream->codec_name == 'png') && $stream->avg_frame_rate == '0/0') {
                    continue;
                }

                if ($stream->codec_type == 'video') {
                    $has_video_stream = true;
                } elseif ($stream->codec_type == 'audio') {
                    $has_audio_stream = true;
                }
            }



            // if no audio or video stream found, invalid (image already tested above).
            if (!$has_video_stream && !$has_audio_stream) {
                return $return;
            }

            // set duration
            $return['duration'] = $mediainfo->format->duration;

            // figure out audio or video
            if ($has_video_stream) {
                $return['type'] = 'video';
            } else {
                $return['type'] = 'audio';
            }

            // figure out format
            if ($return['type'] == 'audio') {
                if ($mediainfo->format->format_long_name == 'QuickTime / MOV') {
                    $return['format'] = 'mp4';
                } else {
                    switch ($mediainfo->format->format_name) {
                        case 'flac':
                            $return['format'] = 'flac';
                            break;

                        case 'mp3':
                            $return['format'] = 'mp3';
                            break;

                        case 'ogg':
                            $return['format'] = 'ogg';
                            break;

                        case 'wav':
                            $return['format'] = 'wav';
                            break;

                        case 'webm':
                            $return['format'] = 'webm';
                            break;
                    }
                }
            } elseif ($return['type'] == 'video') {
                if ($mediainfo->format->format_long_name == 'QuickTime / MOV') {
                    $return['format'] = 'mov';
                } else {
                    switch ($mediainfo->format->format_name) {
                        case 'avi':
                            $return['format'] = 'avi';
                            break;

                        case 'mpeg':
                            $return['format'] = 'mpg';
                            break;

                        case 'ogg':
                            $return['format'] = 'ogg';
                            break;

                        case 'asf':
                            $return['format'] = 'wmv';
                            break;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Initialize database query by adding several parts in advance, such as what
     * items to get (media ID, title, artist, some custom metadata, etc).
     */
    public function get_init_what($args = [])
    {
        OBFHelpers::require_args($args, ['metadata_fields']);

        $this->db->what('media.id', 'id');
        $this->db->what('media.title', 'title');
        $this->db->what('media.artist', 'artist');
        $this->db->what('media.album', 'album');
        $this->db->what('media.year', 'year');

        $this->db->what('media.type', 'type');
        $this->db->what('media.format', 'format');

        $this->db->what('media.category_id', 'category_id');
        $this->db->what('media_categories.name', 'category_name');

        $this->db->what('media.country', 'country');
        $this->db->what('countries.name', 'country_name');

        $this->db->what('media.language', 'language');
        $this->db->what('languages.ref_name', 'language_name');

        $this->db->what('media.is_approved', 'is_approved');

        $this->db->what('media.genre_id', 'genre_id');
        $this->db->what('media_genres.name', 'genre_name');

        $this->db->what('media.comments', 'comments');

        $this->db->what('media.filename', 'filename');
        $this->db->what('media.file_hash', 'file_hash');
        $this->db->what('media.file_location', 'file_location');

        $this->db->what('media.is_copyright_owner', 'is_copyright_owner');
        $this->db->what('media.duration', 'duration');
        $this->db->what('media.owner_id', 'owner_id');
        $this->db->what('media.created', 'created');
        $this->db->what('media.updated', 'updated');
        $this->db->what('media.is_archived', 'is_archived');
        $this->db->what('media.status', 'status');
        $this->db->what('media.dynamic_select', 'dynamic_select');

        $this->db->what('users.display_name', 'owner_name');

        foreach ($args['metadata_fields'] as $metadata_field) {
            $default = $metadata_field['settings']->default ?? null;
            if (is_array($default)) {
                $default = implode(',', $default);
            }

            $this->db->what('COALESCE(media.metadata_' . $metadata_field['name'] . ',"' . $this->db->escape($default) . '")', 'metadata_' . $metadata_field['name'], false);
        }
    }

    /**
     * Initialize database by adding parts in advance, by joining the media table
     * with the media categories, media languages, media countries, media genres,
     * media metadata, and users tables.
     */
    public function get_init_join($args = [])
    {
        $this->db->leftjoin('media_categories', 'media_categories.id', 'media.category_id');
        $this->db->leftjoin('languages', 'media.language', 'languages.language_id');
        $this->db->leftjoin('countries', 'media.country', 'countries.country_id');
        $this->db->leftjoin('media_genres', 'media.genre_id', 'media_genres.id');
        $this->db->leftjoin('users', 'media.owner_id', 'users.id');
    }

    /**
     * Combine the get_init_what and get_init_join methods to set up the initial
     * part of a media query.
     */
    public function get_init($args = [])
    {
        $metadata_fields = $this->models->mediametadata('get_all');

        $this('get_init_what', ['metadata_fields' => $metadata_fields]);
        $this('get_init_join');
    }

    /**
     * Get a media item.
     *
     * @param id
     *
     * @return media
     */
    public function get_by_id($args = [])
    {
        OBFHelpers::require_args($args, ['id']);

        $this('get_init');

        $this->db->where('media.id', $args['id']);
        $media = $this->db->get_one('media');

        // DEPRECATED: Old media thumbnail code (interferes with new thumbnail items).
        /* if ($media) {
            $media['thumbnail'] = $this->models->media('media_thumbnail_exists', ['media' => $media]);
        } */

        return $media;
    }

    /**
     * Check if media thumbnail exists (create if necessary).
     *
     * @param media ID or media row array.
     */
    public function media_thumbnail_exists($args = [])
    {
        OBFHelpers::require_args($args, ['media']);

        if (!is_array($args['media'])) {
            $this->db->where('id', $args['media']);
            $media = $this->db->get_one('media');
        } else {
            $media = $args['media'];
        }

        OBFHelpers::require_args($media, ['type', 'is_archived', 'is_approved', 'file_location']);
        if (strlen($media['file_location']) != 2) {
            trigger_error('Invalid media file location.', E_USER_WARNING);
            return false;
        }

        $thumbnail_directory = OB_CACHE . '/thumbnails/' . $media['file_location'][0] . '/' . $media['file_location'][1];
        $thumbnail_file = $thumbnail_directory . '/' . $media['id'] . '.jpg';

        if (!file_exists($thumbnail_directory)) {
            mkdir($thumbnail_directory, 0755, true);
        }

        if ($media['type'] == 'image' && !file_exists($thumbnail_file)) {
            if ($media['is_archived'] == 1) {
                $media_file = OB_MEDIA_ARCHIVE;
            } elseif ($media['is_approved'] == 0) {
                $media_file = OB_MEDIA_UPLOADS;
            } else {
                $media_file = OB_MEDIA;
            }
            $media_file .= '/' . $media['file_location'][0] . '/' . $media['file_location'][1];
            $media_file = $media_file . '/' . $media['filename'];
            OBFHelpers::image_resize($media_file, $thumbnail_file, 600, 600);
        }

        return file_exists($thumbnail_file);
    }

    /**
     * Get permissions linked to a media item.
     *
     * @param media_id
     *
     * @return [groups, users]
     */
    public function get_permissions($args = [])
    {
        OBFHelpers::require_args($args, ['media_id']);

        $return = [];
        $return['groups'] = [];
        $return['users'] = [];

        $this->db->where('media_id', $args['media_id']);
        $groups = $this->db->get('media_permissions_groups');
        foreach ($groups as $group) {
            $return['groups'][] = (int) $group['group_id'];
        }

        $this->db->where('media_id', $args['media_id']);
        $users = $this->db->get('media_permissions_users');
        foreach ($users as $user) {
            $return['users'][] = (int) $user['user_id'];
        }

        return $return;
    }

    /**
     * Get a user's default filters for searching media.
     *
     * @param user_id
     *
     * @return filters
     */
    public function search_get_default_filters($args = [])
    {
        OBFHelpers::require_args($args, ['user_id']);

        $this->db->where('user_id', $args['user_id']);
        $this->db->where('default', 1);
        $search = $this->db->get_one('media_searches');

        if (!$search) {
            return false;
        }

        $query = unserialize($search['query']);
        return $query['filters'];
    }

    /**
     * Set a user's saved search as the default search.
     *
     * @param id ID of the search to set as default.
     * @param user_id
     *
     * @return success
     */
    public function search_default($args = [])
    {
        OBFHelpers::require_args($args, ['id', 'user_id']);

        $this->db->where('user_id', $args['user_id']);
        $this->db->update('media_searches', array('default' => 0));

        $this->db->where('user_id', $args['user_id']);
        $this->db->where('id', $args['id']);
        $this->db->where('type', 'saved'); // can only make 'saved' searches the default.

        if ($this->db->update('media_searches', array('default' => 1))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unset any custom searches set as the default for a user.
     *
     * @param user_id
     */
    public function search_unset_default($args = [])
    {
        OBFHelpers::require_args($args, ['user_id']);

        $this->db->where('user_id', $args['user_id']);
        $this->db->update('media_searches', array('default' => 0));
        return true;
    }

    /**
     * Save a search query. Uses the currently logged in user's ID to determine
     * the user_id for the saved search, rather than accepting it as a parameter.
     *
     * @param query
     */
    public function search_save($args = [])
    {
        OBFHelpers::require_args($args, ['query']);

        if (!$this->user->param('id')) {
            return false;
        }

        $query = serialize($args['query']);

        $this->db->where('user_id', $this->user->param('id'));
        $this->db->where('query', $query);

        $history_item = $this->db->get_one('media_searches');

        // is this search already saved? then don't do anything.
        if ($history_item && $history_item['type'] == 'saved') {
            return true;
        }

        // is this search item saved to history? then remove it so it gets re-added with a higher ID (to show up as more recent).
        if ($history_item && $history_item['type'] == 'history') {
            $this->db->where('id', $history_item['id']);
            $this->db->delete('media_searches');
        }

        $this->db->insert('media_searches', array('user_id' => $this->user->param('id'),'query' => $query,'type' => 'history'));

        // shrink our history list down to the top 5.
        $this->db->query('select id from media_searches where user_id = "' . $this->db->escape($this->user->param('id')) . '" and type="history"');
        $num_to_delete = $this->db->num_rows() - 5;
        if ($num_to_delete > 0) {
            $this->db->query('delete from media_searches where user_id = "' . $this->db->escape($this->user->param('id')) . '" and type="history" order by id limit ' . $num_to_delete);
        }

        return true;
    }

    /**
     * Retrieve a saved search of the currently logged in user (does not accept
     * a user ID parameter) of the provided type.
     *
     * @param type
     *
     * @return searches
     */
    public function search_get_saved($args = [])
    {
        OBFHelpers::default_args($args, ['type' => 'history']);

        $this->db->what('id');
        $this->db->what('query');
        $this->db->what('default');
        $this->db->what('description');

        $this->db->where('user_id', $this->user->param('id'));
        $this->db->where('type', $args['type']);

        $this->db->orderby('id', 'desc');

        $searches = $this->db->get('media_searches');

        foreach ($searches as $index => $search) {
            $searches[$index]['query'] = unserialize($search['query']);
        }

        return $searches;
    }

    /**
    * Save a search query in the history as a saved search.
    *
    * @param id
    * @param user_id Optional. Additional WHERE qualifier to find the search.
    */
    public function search_save_history($args = [])
    {
        OBFHelpers::require_args($args, ['id']);
        OBFHelpers::default_args($args, ['user_id' => false]);

        if (!$args['id']) {
            return false;
        }

        $this->db->where('id', $args['id']);
        if ($args['user_id']) {
            $this->db->where('user_id', $args['user_id']);
        }

        return $this->db->update('media_searches', array('type' => 'saved'));
    }

    /**
     * Delete an item in the media searches table.
     *
     * @param id
     * @param user_id Optional. Additional WHERE qualifier to find the search.
     */
    public function search_delete_saved($args = [])
    {
        OBFHelpers::require_args($args, ['id']);
        OBFHelpers::default_args($args, ['user_id' => false]);

        if (!$args['id']) {
            return false;
        }

        $this->db->where('id', $args['id']);
        if ($args['user_id']) {
            $this->db->where('user_id', $args['user_id']);
        }

        return $this->db->delete('media_searches');
    }

    /**
     * Edit an item in the media searches table.
     *
     * @param id
     * @param filters
     * @param description
     * @param user_id Optional. Additional WHERE qualifier to find the search.
     */
    public function search_edit($args = [])
    {
        OBFHelpers::require_args($args, ['id', 'filters', 'description']);
        OBFHelpers::default_args($args, ['user_id' => false]);

        if (!$this->search_filters_validate(['filters' => $args['filters']])) {
            return false;
        }

        if ($args['user_id']) {
            $this->db->where('user_id', $args['user_id']);
        }
        $this->db->where('id', $args['id']);
        $query = array('mode' => 'advanced','filters' => $args['filters']);
        $this->db->update('media_searches', array('query' => serialize($query),'description' => $args['description']));

        return true;
    }

    /**
     * Search media items.
     *
     * @param params
     * @param player_id Optional. Specify player to search associated media items.
     * @param random_order Dont bother ordering data. FALSE by default.
     * @param include_private Bypass private media restriction (shows private media not owned by current owner, does not require manage media permission or player_id).
     *
     * @return [media, total_media_found]
     */
    public function search($args = [])
    {
        OBFHelpers::require_args($args, ['params']);
        OBFHelpers::default_args($args['params'], ['sort_by' => null]);
        OBFHelpers::default_args($args, ['player_id' => false, 'random_order' => false, 'include_private' => false]);

        // if we are accessing from a remote, determine the valid media types.
        if ($args['player_id']) {
            $this->db->where('id', $args['player_id']);
            $player = $this->db->get_one('players');

            if (!$player) {
                return false;
            }

            $supported_types = array();

            if ($player['support_audio'] == 1) {
                $supported_types[] = 'media.type = "audio"';
            }
            if ($player['support_video'] == 1) {
                $supported_types[] = 'media.type = "video"';
            }
            if ($player['support_images'] == 1) {
                $supported_types[] = 'media.type = "image"';
            }

            if (count($supported_types) == 0) {
                return false;
            }
        }

        // default status is "approved"

        $where_array = array();
        $params = $args['params'];

        if (isset($params['status']) && $params['status'] == 'archived') {
            $where_array[] = 'is_archived = 1';
        } elseif (isset($params['status']) && $params['status'] == 'unapproved') {
            $where_array[] = '(is_approved = 0 and is_archived = 0)';
        } else {
            $where_array[] = '(is_approved = 1 and is_archived = 0)';
        }

        // only select media (if remote) where dynamic selection is allowed.
        if ($args['player_id']) {
            $where_array[] = 'dynamic_select = 1';
            $where_array[] = '(' . implode(' OR ', $supported_types) . ')';
        }

        // if we don't have "manage_media" permission, we can't view others' private media.
        if (!$args['include_private'] && !$args['player_id'] && !$this->user->check_permission('manage_media')) {
            $where_array[] = '(status = "public" OR status = "visible" OR owner_id = "' . $this->db->escape($this->user->param('id')) . '")';
        }

        //if($random_order) $this('get_init_join');
        //else $this('get_init');
        //if(!$random_order) $this('get_init');

        // limit by id?
        if (!empty($params['id'])) {
            $where_array[] = 'media.id = "' . $this->db->escape($params['id']) . '"';
        }

        // simple search
        if ($params['query']['mode'] == 'simple') {
            $params['query']['string'] = trim($params['query']['string']);
            if ($params['query']['string'] !== '') {
                $where_array[] = '(artist like "%' . $this->db->escape($params['query']['string']) . '%" or title like "%' . $this->db->escape($params['query']['string']) . '%")';
            }
            if (isset($params['default_filters'])) {
                if (!$this->search_filters_validate(['filters' => $params['default_filters']])) {
                    return false;
                }
                $where_array = array_merge($where_array, $this->search_filters_where_array(['filters' => $params['default_filters']]));
            }
        } elseif ($params['query']['mode'] == 'advanced') {
            // advanced search
            if (!$this->search_filters_validate(['filters' => $params['query']['filters']])) {
                return false;
            }
            $where_array = array_merge($where_array, $this->search_filters_where_array(['filters' => $params['query']['filters']]));
        } else {
            return false;
        } // invalid mode.

        // limit results to those owned by the presently logged in user.
        if (isset($params['my']) && $params['my']) {
            $where_array[] = 'owner_id = "' . $this->db->escape($this->user->param('id')) . '"';
        }

        // put all the where data together.
        $this->db->where_string(implode(' AND ', $where_array));
        $this->db->leftjoin('media_genres', 'media.genre_id', 'media_genres.id');
        $this->db->leftjoin('media_categories', 'media.category_id', 'media_categories.id');
        $this->db->leftjoin('countries', 'media.country', 'countries.country_id');
        $this->db->leftjoin('languages', 'media.language', 'languages.language_id');

        if ($params['sort_by'] == 'category_name') {
            $params['sort_by'] = 'media_categories.name';
        } elseif ($params['sort_by'] == 'genre_name') {
            $params['sort_by'] = 'media_genres.name';
        } elseif ($params['sort_by'] == 'country_name') {
            $params['sort_by'] = 'countries.name';
        } elseif ($params['sort_by'] == 'language_name') {
            $params['sort_by'] = 'languages.ref_name';
        }

        if (!$args['random_order']) {
            if (!empty($params['offset'])) {
                $this->db->offset($params['offset']);
            }
            if (!empty($params['limit'])) {
                $this->db->limit($params['limit']);
            }

            // otherwise, if posted sort by data is valid, use that...
            if (isset($params['sort_dir']) && ($params['sort_dir'] == 'asc' || $params['sort_dir'] == 'desc') && array_search($params['sort_by'], array('artist','album','title','year','media_categories.name','media_genres.name','countries.name','languages.ref_name','duration','updated')) !== false) {
                $this->db->orderby($params['sort_by'], $params['sort_dir']);
            } else {
                // otherwise, show the most recently updated first
                $this->db->orderby('updated', 'desc');
            }

            $this->db->calc_found_rows();
            $this->db->what('media.id');
            $media = $this->db->get('media');

            $total_media_found = $this->db->found_rows();

            if (empty($media)) {
                return array(array(), $total_media_found);
            }

            $ids = [];
            foreach ($media as $item) {
                $ids[] = (int) $item['id'];
            }

            $this('get_init');
            $this->db->where_string('media.id IN(' . implode(',', $ids) . ')');
            $this->db->orderby_string('FIELD(media.id,' . implode(',', $ids) . ')');

            $items = $this->db->get('media');
            foreach ($items as &$item) {
                $item['thumbnail'] = $this->models->media('media_thumbnail_exists', ['media' => $item]);
            }

            return array($items,$total_media_found);
        } else {
            $this->db->what('media.id');
            $media = $this->db->get('media');

            $total_media_found = count($media);

            if (!$total_media_found) {
                return array(array(), 0);
            }

            if (!empty($params['limit']) && $params['limit'] > 0) {
                $limit = min($params['limit'], $total_media_found);
            } else {
                $limit = $total_media_found;
            }

            $media_keys = array_rand($media, $limit);
            if (!is_array($media_keys)) {
                $media_keys = array($media_keys);
            }
            shuffle($media_keys); // array_rand does random selection, shuffle does random order.

            $ids = [];
            foreach ($media_keys as $key) {
                $ids[] = (int) $media[$key]['id'];
            }

            $this('get_init');
            $this->db->where_string('media.id IN(' . implode(',', $ids) . ')');
            $this->db->orderby_string('FIELD(media.id,' . implode(',', $ids) . ')');

            $items = $this->db->get('media');
            foreach ($items as &$item) {
                $item['thumbnail'] = $this->models->media('media_thumbnail_exists', ['media' => $item]);
            }

            return array($items,$total_media_found);
        }
    }

    /**
     * Validate search filters. Ensures that filters only contain recognized fields
     * and operators.
     *
     * @param filters
     *
     * @return is_valid
     */
    public function search_filters_validate($args = [])
    {
        OBFHelpers::require_args($args, ['filters']);
        $filters = $args['filters'];

        $allowed_filters = ['comments','artist','title','album','year','type','category','country','language','genre','duration','is_copyright_owner'];

        $metadata_fields = $this->models->mediametadata('get_all');

        foreach ($metadata_fields as $metadata_field) {
            $allowed_filters[] = 'metadata_' . $metadata_field['name'];
        }

        foreach ($filters as $filter) {
            if (is_object($filter)) {
                $filter = get_object_vars($filter);
            }

            // make sure our search field and comparison operator is valid
            if (array_search($filter['filter'], $allowed_filters) === false) {
                return false;
            }
            if (array_search($filter['op'], array('like','not_like','is','not','gte','lte','has','not_has')) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return a 'WHERE' SQL array from a search filters array.
     *
     * @param filters
     *
     * @return where_array
     */
    public function search_filters_where_array($args = [])
    {
        OBFHelpers::require_args($args, ['filters']);
        $filters = $args['filters'];

        $where_array = [];

        foreach ($filters as $filter) {
            if (is_object($filter)) {
                $filter = get_object_vars($filter);
            }

            // our possible column (mappings)
            $column_array = [];
            $column_array['artist'] = 'media.artist';
            $column_array['title'] = 'media.title';
            $column_array['album'] = 'media.album';
            $column_array['year'] = 'media.year';
            $column_array['type'] = 'media.type';
            $column_array['category'] = 'media.category_id';
            $column_array['country'] = 'media.country';
            $column_array['language'] = 'media.language';
            $column_array['genre'] = 'media.genre_id';
            $column_array['duration'] = 'media.duration';
            $column_array['comments'] = 'media.comments';
            $column_array['is_copyright_owner'] = 'media.is_copyright_owner';

            $metadata_fields = $this->models->mediametadata('get_all');
            $metadata_defaults = [];
            foreach ($metadata_fields as $metadata_field) {
                $column_array['metadata_' . $metadata_field['name']] = 'media.metadata_' . $metadata_field['name'];
                if (isset($metadata_field['settings']->default)) {
                    $default = $metadata_field['settings']->default;

                    // make lowercase for case insensitive comparison
                    if (is_array($default)) {
                        $default = array_map('strtolower', $default);
                    } else {
                        $default = strtolower($default);
                    }

                    // keep track for comparison below
                    $metadata_defaults['metadata_' . $metadata_field['name']] = $default;
                }
            }

            // find_in_set works a bit differently
            if ($filter['op'] == 'has' || $filter['op'] == 'not_has') {
                if (isset($metadata_defaults[$filter['filter']])) {
                    $default = $metadata_defaults[$filter['filter']];
                    if (is_array($default)) {
                        $default = implode(',', $default);
                    }

                    // null coalesce to use default value instead of column value if column is null
                    $set = 'COALESCE(' . $this->db->format_table_column($column_array[$filter['filter']]) . ',"' . $this->db->escape($default) . '")';
                } else {
                    $set = $this->db->format_table_column($column_array[$filter['filter']]);
                }

                $tmp_sql = 'FIND_IN_SET("' . $this->db->escape($filter['val']) . '",' . $set . ')';
                if ($filter['op'] == 'not_has') {
                    $tmp_sql = 'NOT ' . $tmp_sql;
                }
            } else {
                // our possibile comparison operators
                $op_array = [];
                $op_array['like'] = 'LIKE';
                $op_array['not_like'] = 'NOT LIKE';
                $op_array['is'] = '=';
                $op_array['not'] = '!=';
                $op_array['gte'] = '>=';
                $op_array['lte'] = '<=';

                // put together our query segment
                if (isset($metadata_defaults[$filter['filter']])) {
                    $default = $metadata_defaults[$filter['filter']];
                    if (is_array($default)) {
                        $default = implode(',', $default);
                    }

                    // null coalesce to use default value instead of column value if column is null
                    $tmp_sql = 'COALESCE(' . $this->db->format_table_column($column_array[$filter['filter']]) . ',"' . $this->db->escape($default) . '")';
                } else {
                    $tmp_sql = $column_array[$filter['filter']];
                }

                $tmp_sql .= ' ' . $op_array[$filter['op']] . ' "';

                if ($filter['op'] == 'like' || $filter['op'] == 'not_like') {
                    $tmp_sql .= '%';
                }
                $tmp_sql .= $this->db->escape($filter['val']);
                if ($filter['op'] == 'like' || $filter['op'] == 'not_like') {
                    $tmp_sql .= '%';
                }
                $tmp_sql .= '"';
            }

            $where_array[] = $tmp_sql;
        }

        return $where_array;
    }

    /**
     * Get information about where media is used.
     *
     * @param id
     * @param include_dynamic Include dynamic selections. FALSE by default.
     *
     * @return [used, id, can_delete]
     */
    public function where_used($args = [])
    {
        OBFHelpers::require_args($args, ['id']);
        OBFHelpers::default_args($args, ['include_dynamic' => false]);
        $id = $args['id'];
        $include_dynamic = $args['include_dynamic'];

        $info = array();

        $info['used'] = array();
        $info['id'] = $id;
        $info['can_delete'] = true;

        // is this used with a playlist?
        $this->db->what('playlists.name', 'name');
        $this->db->what('playlists.id', 'playlist_id');
        $this->db->where('playlists_items.item_id', $id);
        $this->db->where('playlists_items.item_type', 'media');
        $this->db->leftjoin('playlists', 'playlists.id', 'playlists_items.playlist_id');
        $playlists = $this->db->get('playlists_items');

        foreach ($playlists as $playlist) {
            $used_data = new stdClass();
            $used_data->where = 'playlist';
            $used_data->id = $playlist['playlist_id'];
            $used_data->name = $playlist['name'];

            $info['used'][] = $used_data;
        }

        // is this potentially found in a dynamic selection?
        if ($include_dynamic) {
      // see if media can actually be used in dynamic selections.
            $this->db->where('id', $id);
            $media = $this->db->get_one('media');

            if ($media['dynamic_select'] == 1) {
                $this->db->what('playlists.name');
                $this->db->what('playlists.id');
                $this->db->what('playlists_items.properties');

                $this->db->where('item_type', 'dynamic');

                $this->db->leftjoin('playlists', 'playlists.id', 'playlists_items.playlist_id');

                $dynamic_items = $this->db->get('playlists_items');

                $found_in_playlists = array();

                foreach ($dynamic_items as $item) {
                    if (array_search($item['id'], $found_in_playlists) !== false) {
                        continue;
                    } // don't search if we've already found it in this playlist.

                    $media_search = $this('search', ['params' => array('limit' => 1,'query' => json_decode($item['properties'], true)['query'],'id' => $id)]);

                    if ($media_search && $media_search[1] > 0) {
                        $used_data = new stdClass();
                        $used_data->where = 'playlist_dynamic';
                        $used_data->id = $item['id'];
                        $used_data->name = $item['name'];

                        $info['used'][] = $used_data;

                        $found_in_playlists[] = $item['id'];
                    }
                }
            }
        }

        // is this used with a station ID?
        $this->db->what('players.name', 'name');
        $this->db->what('players.id', 'player_id');
        $this->db->where('players_station_ids.media_id', $id);
        $this->db->leftjoin('players', 'players_station_ids.player_id', 'players.id');
        $station_ids = $this->db->get('players_station_ids');

        foreach ($station_ids as $station_id) {
            if (!$this->user->check_permission('manage_players')) {
                $info['can_delete'] = false;
            }

            $used_data = new stdClass();
            $used_data->where = 'player';
            $used_data->id = $station_id['player_id'];
            $used_data->name = $station_id['name'];

            $info['used'][] = $used_data;
        }

        // is this used with an alert broadcast?
        $this->db->where('item_id', $id);
        $alerts = $this->db->get('alerts');

        foreach ($alerts as $alert) {
            if (!$this->user->check_permission('manage_alerts')) {
                $info['can_delete'] = false;
            }

            $used_data = new stdClass();
            $used_data->where = 'alert';
            $used_data->name = $alert['name'];
            $used_data->user_id = $alert['user_id'];
            $used_data->id = $alert['id'];

            $info['used'][] = $used_data;
        }

        // is this used on a show?
        $this->db->what('players.id', 'player_id');
        $this->db->what('players.name', 'player_name');
        $this->db->what('shows.user_id', 'user_id');
        $this->db->what('shows.id', 'id');
        $this->db->where('item_id', $id);
        $this->db->where('item_type', 'media');
        $this->db->leftjoin('players', 'shows.player_id', 'players.id');
        $shows = $this->db->get('shows');

        foreach ($shows as $show) {
            if ($show['user_id'] != $this->user->param('id') && !$this->user->check_permission('manage_timeslots')) {
                $info['can_delete'] = false;
            }

            $used_data = new stdClass();
            $used_data->where = 'show';
            $used_data->name = $show['player_name'];
            $used_data->id = $show['id'];
            $used_data->user_id = $show['user_id'];

            $info['used'][] = $used_data;
        }

        return $info;
    }

    /**
     * Validate a media item before inserting or updating.
     *
     * @param item Media item to validate.
     * @param skip_upload_check Upload check is done by default, set to TRUE to skip.
     *
     * @param is_valid
     */
    public function validate($args = [])
    {
        OBFHelpers::require_args($args, ['item']);
        OBFHelpers::default_args($args, ['skip_upload_check' => false]);
        $item = $args['item'];
        $skip_upload_check = $args['skip_upload_check'];

        // check if id is valid (if editing)
        //T This media item no longer exists.
        if (!empty($item['id']) && !$this->db->id_exists('media', $item['id'])) {
            return array(false,$item['local_id'],'This media item no longer exists.');
        }

        // check if file exists (if uploading)
        //T The file upload is not valid.
        if (!empty($item['file_id']) && !$item['file_info']) {
            return array(false,$item['local_id'],'The file upload is not valid.');
        }

        // require uploading for new item.
        //T A file upload is required for new media.
        if (!$skip_upload_check && empty($item['id']) && empty($item['file_id'])) {
            return array(false,$item['local_id'],'A file upload is required for new media.');
        }

        // check if format valid/allowed (if uploading)
        //T This file format is not supported.
        if (!empty($item['file_id']) && !$this('format_allowed', ['type' => $item['file_info']['type'], 'format' => $item['file_info']['format']])) {
            return array(false,$item['local_id'],'This file format is not supported.');
        }

        // Make sure title field is set - this is the one field that's always required.
        if (empty($item['title'])) {
            //T One or more required fields were not filled.
            return array(false, $item['local_id'],'One or more required fields were not filled.');
        }

        // Get the required fields from media metadata settings and test them against
        // provided data.
        $req_fields     = $this->models->mediametadata('get_fields');

        if (!$req_fields[0]) {
            return array(false, $item['local_id'], 'Unable to load required fields from settings.');
        }

        foreach ($req_fields[2] as $field => $req) {
            if (empty($item[$field]) && $req === 'required') {
                return array(false, $item['local_id'], 'Field "' . $field . '" is required.');
            }
        }

        // make sure artist and title aren't too long.  letting the db do the truncating messes up the filename.
        //T One or more artist/title fields are too long.
        if (strlen($item['artist']) > 255 || strlen($item['title']) > 255) {
            return array(false,$item['local_id'],'One or more artist/title fields are too long.');
        }

        // check if year valid
        //T The year is not valid.
        if (!empty($item['year']) && (!preg_match('/^[0-9]+$/', $item['year']) || $item['year'] > 2100)) {
            return array(false,$item['local_id'],'The year is not valid.');
        }

        // validate select fields

        //T The category selected is no longer valid.
        if (!empty($item['category_id']) && !$this->db->id_exists('media_categories', $item['category_id'])) {
            return array(false,$item['local_id'],'The category selected is no longer valid.');
        }
        //T The country selected is no longer valid.
        $this->db->where('country_id', $item['country']);
        $exists = $this->db->get_one('countries');
        if (!empty($item['country']) && !$exists) {
            return array(false,$item['local_id'],'The country selected is no longer valid.');
        }
        //T The genre selected is no longer valid.
        if (!empty($item['genre_id']) && !$this->db->id_exists('media_genres', $item['genre_id'])) {
            return array(false,$item['local_id'],'The genre selected is no longer valid.');
        }
        //T The language selected is no longer valid.
        $this->db->where('language_id', $item['language']);
        $exists = $this->db->get_one('languages');
        if (!empty($item['language']) && !$exists) {
            return array(false,$item['local_id'],'The language selected is no longer valid.');
        }

        //T The media status is not valid.
        if ($item['status'] != 'private' && $item['status'] != 'visible' && $item['status'] != 'public') {
            return array(false,$item['local_id'],'The media status is not valid.');
        }

        // make sure genre belongs to the selected category.
        if (!empty($item['genre_id'])) {
            $this->db->where('id', $item['genre_id']);
            $genre = $this->db->get_one('media_genres');
            //T The selected genre is not available for the selected category.
            if ($genre['media_category_id'] != $item['category_id']) {
                return array(false,$item['local_id'],'The selected genre is not available for the selected category.');
            }
        }

        // validate custom metadata
        $metadata_fields = $this->models->mediametadata('get_all');
        foreach ($metadata_fields as $metadata_field) {
            // ignore if not set; no "required" option yet.
            if (!isset($item['metadata_' . $metadata_field['name']])) {
                continue;
            }

            $metadata_value = $item['metadata_' . $metadata_field['name']];

            // validate if select
            if ($metadata_field['type'] == 'select' && $metadata_value != '' && array_search($metadata_value, $metadata_field['settings']->options) === false) {
                return array(false,$item['local_id'],$metadata_field['description'] . ' value not valid.');
            }

            // validate if bool
            if ($metadata_field['type'] == 'bool' && $metadata_value != '' && array_search($metadata_value, [0,1]) === false) {
                return array(false,$item['local_id'],$metadata_field['description'] . ' value not valid.');
            }

            // validate if tags
            if ($metadata_field['type'] == 'tags' && !is_array($metadata_value)) {
                return array(false,$item['local_id'],$metadata_field['description'] . ' value not valid.');
            }
        }

        // not bothering to validate yes/no... if not 1 (yes), assuming 0 (no).

        return array(true,$item['local_id']);
    }

    /**
     * Insert or update a media item.
     *
     * @param item
     *
     * @return id
     */
    public function save($args = [])
    {
        mysqli_report(MYSQLI_REPORT_ERROR);

        OBFHelpers::require_args($args, ['item']);
        $item = $args['item'];

        // grab some important values
        $id = isset($item['id']) ? $item['id'] : null;
        $file_id = $item['file_id'];
        $file_info = (isset($item['file_info']) ? $item['file_info'] : null);

        $trimStart = $item['trim_start'] ?? null;
        $trimEnd = $item['trim_end'] ?? null;

        // get our original item before edit (we may need this)
        if ($id) {
            $this->db->where('id', $id);
            $original_media = $this->db->get_one('media');
        } else {
            $original_media = false;
        }

        // override dynamic select field if hidden
        $req_fields = $this->models->mediametadata('get_fields')[2];
        if ($req_fields['dynamic_content_hidden']) {
            if ($original_media) {
                $item['dynamic_select'] = $original_media['dynamic_select'];
            } else {
                $item['dynamic_select'] = $req_fields['dynamic_content_default'] == 'enabled' ? 1 : 0;
            }
        }

        // some data might need cleanup
        if ($item['is_copyright_owner'] != 1) {
            $item['is_copyright_owner'] = 0;
        }
        if ($item['is_approved'] != 1) {
            $item['is_approved'] = 0;
        }
        if ($item['dynamic_select'] != 1) {
            $item['dynamic_select'] = 0;
        }

        // if inadequate permission, is_approved defaults to 0.
        if (!$this->user->check_permission('approve_own_media or manage_media')) {
            if ($id) {
                unset($item['is_approved']);
            } else {
                $item['is_approved'] = 0;
            }
        }

        // if inadequate permission, is_copyright_owner defaults to 0.
        if (!$this->user->check_permission('copyright_own_media or manage_media')) {
            if ($id) {
                unset($item['is_copyright_owner']);
            } else {
                $item['is_copyright_owner'] = 0;
            }
        }

        // if inadequate permission, public status changes to visible
        if (($item['is_copyright_owner'] ?? 0) && $item['status'] == 'public' && !$this->user->check_permission('allow_copyright_public')) {
            if ($id) {
                unset($item['status']);
            } else {
                $item['status'] = 'visible';
            }
        }
        if (!($item['is_copyright_owner'] ?? 0) && $item['status'] == 'public' && !$this->user->check_permission('allow_noncopyright_public')) {
            if ($id) {
                unset($item['status']);
            } else {
                $item['status'] = 'visible';
            }
        }

        // set null values where appropriate
        if (empty($item['artist'])) {
            $item['artist'] = '';
        }
        if (empty($item['album'])) {
            $item['album'] = '';
        }
        if (empty($item['category_id'])) {
            $item['category_id'] = null;
        }
        if (empty($item['country'])) {
            $item['country'] = null;
        }
        if (empty($item['language'])) {
            $item['language'] = null;
        }
        if (empty($item['genre_id'])) {
            $item['genre_id'] = null;
        }
        if (empty($item['year'])) {
            $item['year'] = null;
        }

        // unset some values.
        unset($item['local_id']);
        unset($item['file_id']);
        unset($item['file_key']);
        unset($item['id']);
        unset($item['file_info']);
        unset($item['thumbnail']);
        unset($item['trim_start']);
        unset($item['trim_end']);

        // separate out advanced permissions if we have them
        $advanced_permissions_users = $item['advanced_permissions_users'] ?? false;
        $advanced_permissions_groups = $item['advanced_permissions_groups'] ?? false;
        if (isset($item['advanced_permissions_users'])) {
            unset($item['advanced_permissions_users']);
        }
        if (isset($item['advanced_permissions_groups'])) {
            unset($item['advanced_permissions_groups']);
        }

        // set our file info if we have it
        if ($file_id) {
            $item['duration'] = $file_info['duration'];
            $item['type'] = $file_info['type'];
            $item['format'] = $file_info['format'];
        }

        // handle custom metadata
        $metadata_fields = $this->models->mediametadata('get_all');
        $metadata = [];
        $metadata_tags = [];

        foreach ($metadata_fields as $metadata_field) {
            if ($metadata_field['type'] == 'tags') {
                $tags = [];
                if (!empty($item['metadata_' . $metadata_field['name']])) {
                    foreach ($item['metadata_' . $metadata_field['name']] as $value) {
                        $value = trim($value);
                        if ($value !== '') {
                            $metadata_tags[] = ['media_metadata_column_id' => $metadata_field['id'], 'tag' => $value];
                            $tags[] = $value;
                        }
                    }
                }
                $metadata['metadata_' . $metadata_field['name']] = implode(',', $tags);
            } else {
                $metadata['metadata_' . $metadata_field['name']] = $item['metadata_' . $metadata_field['name']] ?? null;
            }

            unset($item['metadata_' . $metadata_field['name']]);
        }

        // update or insert.
        if (!empty($id)) {
            $this->db->where('id', $id);
            $item['updated'] = time();
            $this->db->update('media', $item);

            // delete from shows_cache where this item has been scheduled.  should regenerate cache.
            $this->db->where_like('data', '"id":"' . $this->db->escape($id) . '"');
            $this->db->delete('shows_cache');
        } else {
            if (!isset($item['owner_id'])) {
                $item['owner_id'] = $this->user->param('id');
            }
            $item['created'] = time();
            $item['updated'] = time();

            $id = $this->db->insert('media', $item);
        }

        // update or insert custom metadata
        $this->db->where('id', $id);
        $this->db->update('media', $metadata);

        // update custom metadata tags
        $this->db->where('media_id', $id);
        $this->db->delete('media_tags');
        foreach ($metadata_tags as $row) {
            $row['media_id'] = $id;
            $this->db->insert('media_tags', $row);
        }

        // determine our file's name (may be used if we have a new file, or file requires renaming)
        // $filename_artist = preg_replace("/[^a-zA-Z0-9]/", "_", $item['artist']);
        // $filename_title = preg_replace("/[^a-zA-Z0-9]/", "_", $item['title']);
        $filename = $id . '.' . (!empty($item['format']) ? $item['format'] : $original_media['format']);

        // handle file if we have it.
        if (!empty($file_id)) {
      // determine our (random) file location

            if ($original_media) {
                $file_location = $original_media['file_location'];
            } else {
                $file_location = $this->rand_file_location();
            }
            $media_location = '/' . $file_location[0] . '/' . $file_location[1] . '/';

            // remove our original file if we have it
            if ($original_media) {
                // if($original_media['is_archived']==1) unlink(OB_MEDIA_ARCHIVE.$media_location); // should not be archived, can not edit archived media.
                if ($original_media['is_approved'] == 0) {
                    unlink(OB_MEDIA_UPLOADS . $media_location . $original_media['filename']);
                } else {
                    unlink(OB_MEDIA . $media_location . $original_media['filename']);
                }
            }

            // move our file to its home
            $file_src = OB_ASSETS . '/uploads/' . $file_id;

            if ($item['is_approved'] == 0) {
                $file_dest = OB_MEDIA_UPLOADS . $media_location . $filename;
            } else {
                $file_dest = OB_MEDIA . $media_location . $filename;
            }

            // trim file first if trim_start and/or trim_end are set
            if ($trimStart || $trimEnd) {
                $mediaStart = floatval($trimStart ?? 0.0);
                $mediaEnd   = floatval($item['duration']) - $mediaStart - floatval($trimEnd ?? 0.0);
                shell_exec('ffmpeg -ss ' . escapeshellarg($mediaStart) . ' -i ' . escapeshellarg($file_src) . ' -t ' . escapeshellarg($mediaEnd) . ' -c copy ' . escapeshellarg($file_dest));
            } else {
                rename($file_src, $file_dest);
            }

            // remove file upload row from uploads table.
            $this->db->where('id', $file_id);
            $this->db->delete('uploads');

            // update our database with file hash, location, filename
            $data['filename'] = $filename;
            $data['file_location'] = $file_location;
            $data['file_hash'] = md5_file($file_dest);

            $this->db->where('id', $id);
            $this->db->update('media', $data);
        } elseif ($original_media && ($original_media['is_approved'] != $item['is_approved'] || $original_media['artist'] != $item['artist'] || $original_media['title'] != $item['title'])) {
            // if we are not uploading new file, but we are changing approved status, we need to move our file. or we're changing the artist or title name (on which the filename is based).
            $media_location = '/' . $original_media['file_location'][0] . '/' . $original_media['file_location'][1] . '/';

            if ($original_media['is_approved'] == 1) {
                $file_src = OB_MEDIA . $media_location . $original_media['filename'];
            } else {
                $file_src = OB_MEDIA_UPLOADS . $media_location . $original_media['filename'];
            }

            if ($item['is_approved'] == 1) {
                $file_dest = OB_MEDIA . $media_location . $filename;
            } else {
                $file_dest = OB_MEDIA_UPLOADS . $media_location . $filename;
            }

            rename($file_src, $file_dest);

            // update db with new filename
            $this->db->where('id', $id);
            $this->db->update('media', array('filename' => $filename));
        }

        // handle advanced permissions if we have them
        if (is_array($advanced_permissions_users)) {
            $this->db->where('media_id', $id);
            $this->db->delete('media_permissions_users');

            foreach ($advanced_permissions_users as $user_id) {
                if ($this->db->id_exists('users', $user_id)) {
                    $this->db->insert('media_permissions_users', [
                    'media_id' => $id,
                    'user_id' => $user_id
                    ]);
                }
            }
        }
        if (is_array($advanced_permissions_groups)) {
            $this->db->where('media_id', $id);
            $this->db->delete('media_permissions_groups');

            foreach ($advanced_permissions_groups as $group_id) {
                if ($this->db->id_exists('users_groups', $group_id)) {
                    $this->db->insert('media_permissions_groups', [
                    'media_id' => $id,
                    'group_id' => $group_id
                    ]);
                }
            }
        }

        return $id;
    }

    /**
     * Get version information about a media item.
     *
     * @param data Media item, should contain 'media_id' to check for versions.
     *
     * @return versions
     */
    public function versions($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        $media_id = $data['media_id'] ?? null;
        $media = $this('get_by_id', ['id' => $media_id]);

        //T Invalid media ID.
        if (!$media) {
            return [false, 'Invalid media ID.'];
        }

        if (!$this('version_add_original', ['data' => ['media_id' => $media_id]])[0]) {
        }

        $this->db->what('active');
        $this->db->what('notes');
        $this->db->what('created');
        $this->db->what('format');
        $this->db->what('duration');
        $this->db->where('media_id', $media_id);
        $this->db->orderby('created', 'desc');

        $versions = $this->db->get('media_versions');

        return [true, 'Media Versions', ['versions' => $versions]];
    }

    /**
     * Add the original version of the media item to the versioning table. Will
     * do nothing if an original version already exists.
     *
     * @param data Media item, should contain 'media_id' for versions table.
     */
    public function version_add_original($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        $media_id = $data['media_id'] ?? null;
        $media = $this('get_by_id', ['id' => $media_id]);

        //T Invalid media ID.
        if (!$media) {
            return [false, 'Invalid media ID.'];
        }

        // create "original" version in db if necessary
        $this->db->where('created', 0);
        $this->db->where('media_id', $media_id);
        if (!$this->db->get_one('media_versions')) {
            $version = [];
            $version['media_id'] = $media_id;
            $version['created'] = 0;
            $version['active'] = 1;
            $version['file_hash'] = $media['file_hash'];
            $version['format'] = $media['format'];
            $version['duration'] = $media['duration'];
            $version['notes'] = '';
            $this->db->insert('media_versions', $version);

            // create original version file
            $dst_dir = (defined('OB_MEDIA_VERSIONS') ? OB_MEDIA_VERSIONS : OB_MEDIA . '/versions') . '/' . $media['file_location'][0] . '/' . $media['file_location'][1];
            if (!is_dir($dst_dir)) {
                mkdir($dst_dir, 0755, true);
            }
            $dst_file = $dst_dir . '/' . $media_id . '-0.' . $media['format'];
            if (!file_exists($dst_file)) {
                if ($media['is_archived'] == 1) {
                    $src_dir = OB_MEDIA_ARCHIVE;
                } elseif ($media['is_approved'] == 0) {
                    $src_dir = OB_MEDIA_UPLOADS;
                } else {
                    $src_dir = OB_MEDIA;
                }
                $src_dir .= '/' . $media['file_location'][0] . '/' . $media['file_location'][1];
                $src_file = $src_dir .= '/' . $media['filename'];

                if (!copy($src_file, $dst_file)) {
                    return [false,'Error creating original version file.'];
                }
            }
        }

        return [true, 'Original version created'];
    }

    /**
     * Add a version item.
     *
     * @param data Should contain 'media_id', 'file_id', and 'file_key' at least.
     */
    public function version_add($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        $media_id = $data['media_id'] ?? null;
        $file_id = $data['file_id'] ?? null;
        $file_key = $data['file_key'] ?? null;

        // make sure we have the info we need

        //T Invalid new version data.
        if (!$media_id || !$file_id || !$file_key) {
            return [false, 'Invalid new version data.'];
        }

        // get media info
        $media = $this('get_by_id', ['id' => $media_id]);
        //T Invalid media ID.
        if (!$media) {
            return [false, 'Invalid media ID.'];
        }

        // get file info
        $this->db->where('id', $file_id);
        $this->db->where('key', $file_key);
        $file_info = $this->db->get_one('uploads');

        //T Invalid upload data.
        if (!$file_info) {
            return [false, 'Invalid upload data.'];
        }

        // validate file info
        //T The file type is invalid.
        if ($file_info['type'] != $media['type']) {
            return [false,'The file type is invalid.'];
        }
        //T The file uploaded has an unsupported format.
        if (!$this('format_allowed', ['type' => $file_info['type'], 'format' => $file_info['format']])) {
            return [false,'The file uploaded has an unsupported format.'];
        }

        // copy version file
        $dst_dir = defined('OB_MEDIA_VERSIONS') ? OB_MEDIA_VERSIONS : OB_MEDIA . '/versions';
        $dst_dir .= '/' . $media['file_location'][0] . '/' . $media['file_location'][1];

        if (!is_dir($dst_dir)) {
            mkdir($dst_dir, 0755, true);
        }

        $created = time();

        $dst_file = $dst_dir . '/' . $media_id . '-' . $created . '.' . $file_info['format'];

        // this is really unlikely
        if (file_exists($dst_file)) {
            return [false,'Error creating new version. Please try again.'];
        }

        // move file
        if (!rename(OB_ASSETS . '/uploads/' . $file_id, $dst_file)) {
            return [false, 'Error adding new version.'];
        }

        // add version to database
        $data = [];
        $data['media_id'] = $media_id;
        $data['created'] = $created;
        $data['active'] = 0;
        $data['file_hash'] = md5_file($dst_file);
        $data['format'] = $file_info['format'];
        $data['duration'] = $file_info['duration'];
        $data['notes'] = '';

        $this->db->insert('media_versions', $data);

        return [true, 'New version added.'];
    }

    /**
     * Update a version.
     *
     * @param data Should contain 'media_id' to identify item and 'created' to identify version.
     */
    public function version_edit($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        $media_id = $data['media_id'] ?? null;
        $created = $data['created'] ?? null;
        $notes = $data['notes'] ?? null;

        $this->db->where('media_id', $media_id);
        $this->db->where('created', $created);
        if (!$this->db->update('media_versions', ['notes' => $notes])) {
            return [false, 'Error editing version.'];
        }
        return [true, 'Version edited.'];
    }

    /**
     * Delete a version.
     *
     * @param data Should contain 'media_id' to identify item and 'created' to identify version.
     */
    public function version_delete($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        $media_id = $data['media_id'] ?? null;
        $created = $data['created'] ?? null;

        // TODO maybe we should be able to do this
        if ($created == 0) {
            return [false,'Cannot delete original version.'];
        }

        $this->db->where('media_id', $media_id);
        $this->db->where('created', $created);
        $version = $this->db->get_one('media_versions');

        if (!$version) {
            return [false,'Version not found.'];
        }
        if ($version['active']) {
            return [false,'Cannot delete active version.'];
        }

        $media = $this('get_by_id', ['id' => $media_id]);
        if (!$media) {
            return [false,'Media not found.'];
        }

        // all good, time to delete
        $this->db->where('id', $version['id']);
        $this->db->delete('media_versions');

        $version_file = (defined('OB_MEDIA_VERSIONS') ? OB_MEDIA_VERSIONS : OB_MEDIA . '/versions') .
                    '/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/' .
                    $version['media_id'] . '-' . $version['created'] . '.' . $version['format'];


        if (!file_exists($version_file) || !unlink($version_file)) {
            return [false, 'Error deleting version file.'];
        }

        return [true, 'Version deleted.'];
    }

    /**
     * Set the version for a media item.
     *
     * @param data Should contain 'media_id' to identify item and 'created' to identify version.
     */
    public function version_set($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        $media_id = $data['media_id'] ?? null;
        $created = $data['created'] ?? null;

        // get media
        $media = $this('get_by_id', ['id' => $media_id]);

        //T Invalid media ID.
        if (!$media) {
            return [false, 'Invalid media ID.'];
        }

        // get version
        $this->db->where('media_id', $media_id);
        $this->db->where('created', $created);
        $version = $this->db->get_one('media_versions');
        if (!$version) {
            return [false,'Version not found.'];
        }

        // make sure we have the original version saved before deleting it from the media directory
        if (!$this('version_add_original', ['data' => ['media_id' => $media_id]])[0]) {
            return [false, 'Error setting version.'];
        }

        // delete current media
        if ($media['is_archived'] == 1) {
            $media_dir = OB_MEDIA_ARCHIVE;
        } elseif ($media['is_approved'] == 0) {
            $media_dir = OB_MEDIA_UPLOADS;
        } else {
            $media_dir = OB_MEDIA;
        }
        $media_dir .= '/' . $media['file_location'][0] . '/' . $media['file_location'][1];
        $old_version_file = $media_dir . '/' . $media['filename'];
        if (!unlink($old_version_file)) {
            return [false, 'Error setting version.'];
        }

        // copy version to media directory and update db media row
        $new_version_src_dir = (defined('OB_MEDIA_VERSIONS') ? OB_MEDIA_VERSIONS : OB_MEDIA . '/versions') . '/' . $media['file_location'][0] . '/' . $media['file_location'][1];
        $new_version_src_file = $new_version_src_dir . '/' . $version['media_id'] . '-' . $version['created'] . '.' . $version['format'];
        $new_version_filename = $version['media_id'] . '.' . $version['format'];
        $new_version_dst_file = $media_dir . '/' . $new_version_filename;

        if (!copy($new_version_src_file, $new_version_dst_file)) {
            return [false, 'Error setting version.'];
        }

        // unset active on all versions
        $this->db->where('media_id', $media_id);
        $this->db->update('media_versions', ['active' => 0]);

        // set active on this version
        $this->db->where('media_id', $media_id);
        $this->db->where('created', $created);
        $this->db->update('media_versions', ['active' => 1]);

        $this->db->where('id', $media_id);
        $this->db->update('media', [
        'format' => $version['format'],
        'duration' => $version['duration'],
        'file_hash' => $version['file_hash'],
        'filename' => $new_version_filename
        ]);

        // delete media cache
        $this->delete_cached(['media' => $media]);

        return [true, 'Version set.'];
    }

    /**
     * Remove all versions for a media item.
     *
     * @param data Should contain 'media_id' to identify associated versions.
     */
    public function versions_delete_all($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        $media_id = $data['media_id'] ?? null;

        $media = $this('get_by_id', ['id' => $media_id]);
        if (!$media) {
            return false;
        }

        $this->db->where('media_id', $media_id);
        $versions = $this->db->get('media_versions');

        foreach ($versions as $version) {
            $version_file = (defined('OB_MEDIA_VERSIONS') ? OB_MEDIA_VERSIONS : OB_MEDIA . '/versions') .
                      '/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/' .
                      $version['media_id'] . '-' . $version['created'] . '.' . $version['format'];

            unlink($version_file);
        }

        $this->db->where('media_id', $media_id);
        $this->db->delete('media_versions');

        return true;
    }

    /**
     * Move approved media items to archive.
     *
     * @param ids Array of media item IDs.
     */
    public function archive($args = [])
    {
        OBFHelpers::require_args($args, ['ids']);
        $ids = $args['ids'];

        $original_media = array();

        // make sure all our media exists. media must not be unapproved or already archived.
        foreach ($ids as $id) {
            $this->db->where('is_approved', 1);
            $this->db->where('is_archived', 0);
            $this->db->where('id', $id);
            $media = $this->db->get_one('media');

            if (!$media) {
                return false;
            }

            $original_media[$id] = $media;
        }

        // proceed with archiving
        foreach ($ids as $id) {
            $where_used = $this('where_used', ['id' => $id]);
            if ($where_used['can_delete'] == false) {
                return false;
            }

            $this->db->where('id', $id);
            $update = $this->db->update('media', array('is_archived' => 1));

            if ($update) {
                $src_file = OB_MEDIA . '/' . $original_media[$id]['file_location'][0] . '/' . $original_media[$id]['file_location'][1] . '/' . $original_media[$id]['filename'];
                $dest_file = OB_MEDIA_ARCHIVE . '/' . $original_media[$id]['file_location'][0] . '/' . $original_media[$id]['file_location'][1] . '/' . $original_media[$id]['filename'];

                if (file_exists($src_file)) {
                    rename($src_file, $dest_file);
                }

                $this('remove_where_used', ['id' => $id]);
            }
        }

        return true;
    }

    /**
     * Unarchive media IDs.
     *
     * @param ids Array of media item IDs.
     */
    public function unarchive($args = [])
    {
        OBFHelpers::require_args($args, ['ids']);
        $ids = $args['ids'];

        $original_media = array();

        // make sure all our media exists. media must already be archived. unarchived media moves back to approved.
        foreach ($ids as $id) {
            $this->db->where('is_archived', 1);
            $this->db->where('id', $id);
            $media = $this->db->get_one('media');

            if (!$media) {
                return false;
            }

            $original_media[$id] = $media;
        }

        // proceed with unarchive
        foreach ($ids as $id) {
            $this->db->where('id', $id);
            // unarchived media must go to approved.
            $update = $this->db->update('media', array('is_archived' => 0, 'is_approved' => 1));

            if ($update) {
                $src_file = OB_MEDIA_ARCHIVE . '/' . $original_media[$id]['file_location'][0] . '/' . $original_media[$id]['file_location'][1] . '/' . $original_media[$id]['filename'];
                $dest_file = OB_MEDIA . '/' . $original_media[$id]['file_location'][0] . '/' . $original_media[$id]['file_location'][1] . '/' . $original_media[$id]['filename'];

                if (file_exists($src_file)) {
                    rename($src_file, $dest_file);
                }
            }
        }

        return true;
    }

    /**
     * Delete media items. Can only remove archived or unapproved items.
     *
     * @param ids Array of media item IDs.
     *
     * @return success
     */
    public function delete($args = [])
    {
        OBFHelpers::require_args($args, ['ids']);
        $ids = $args['ids'];

        $original_media = array();

        // make sure we have all our media and it's already archived or still unapproved.
        foreach ($ids as $id) {
            $media = $this('get_by_id', ['id' => $id]);

            if (!$media || ($media['is_archived'] == 0 && $media['is_approved'] == 1)) {
                return false;
            }

            $original_media[$id] = $media;
        }

        // proceed with delete
        foreach ($ids as $id) {
            $this->versions_delete_all(['data' => ['media_id' => $id]]);

            // main delete
            $this->db->where('id', $id);
            $this->db->delete('media');

            if ($original_media[$id]['is_archived'] == 1) {
                $media_file = OB_MEDIA_ARCHIVE . '/' . $original_media[$id]['file_location'][0] . '/' . $original_media[$id]['file_location'][1] . '/' . $original_media[$id]['filename'];
            } else {
                $media_file = OB_MEDIA_UPLOADS . '/' . $original_media[$id]['file_location'][0] . '/' . $original_media[$id]['file_location'][1] . '/' . $original_media[$id]['filename'];
            }

            if (file_exists($media_file)) {
                unlink($media_file);
            }

            $this->delete_cached(['media' => $original_media[$id]]);

            // store metadata of deleted media in case needed for reporting, etc.
            $this->db->insert('media_deleted', [
            'media_id' => $id,
            'metadata' => json_encode($original_media[$id])
            ]);

            // remove where used necessary if deleting without archiving
            $this('remove_where_used', ['id' => $id]);
        }

        return true;
    }

    /**
     * Delete cached media item.
     *
     * @param media
     */
    public function delete_cached($args = [])
    {
        OBFHelpers::require_args($args, ['media']);
        $media = $args['media'];

        // make sure cache dir exists (otherwise nothing to delete anyway)
        if (!is_dir(OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1])) {
            return;
        }

        // remove any cached preview files for this item.
        $dh = opendir(OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1]);

        while (false !== ($entry = readdir($dh))) {
            if (strpos($entry, $media['id'] . '_') === 0) {
                unlink(OB_CACHE . '/media/' . $media['file_location'][0] . '/' . $media['file_location'][1] . '/' . $entry);
            }
        }
    }

    /**
     * Validate media formats.
     *
     * @param data Array of format strings.
     *
     * @return [is_valid, msg]
     */
    public function formats_validate($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        foreach ($data as $name => $value) {
            $$name = $value;
        }

        // list of valid formats...
        $valid_video_formats = array('avi','mpg','ogg','wmv','mov');
        $valid_image_formats = array('jpg','png','svg');
        $valid_audio_formats = array('flac','mp3','ogg','webm','mp4','wav');

        // verify image formats
        if (!is_array($video_formats) || !is_array($image_formats) || !is_array($audio_formats)) {
            return array(false,'There was a problem saving the format settings.');
        }

        foreach ($video_formats as $format) {
            if (array_search($format, $valid_video_formats) === false) {
                return array(false,'There was a problem saving the format settings. One of the formats does not appear to be valid.');
            }
        }

        foreach ($audio_formats as $format) {
            if (array_search($format, $valid_audio_formats) === false) {
                return array(false,'There was a problem saving the format settings. One of the formats does not appear to be valid.');
            }
        }

        foreach ($image_formats as $format) {
            if (array_search($format, $valid_image_formats) === false) {
                return array(false,'There was a problem saving the format settings. One of the formats does not appear to be valid.');
            }
        }

        return array(true,'');
    }

    /**
     * Save accepted media formats.
     *
     * @param data Multidimensional ('audio_formats', etc.) array of accepted formats.
     */
    public function formats_save($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        $data = $args['data'];

        foreach ($data as $name => $value) {
            $$name = $value;
        }

        $this->db->where('name', 'audio_formats');
        $this->db->update('settings', array('value' => implode(',', $audio_formats)));

        $this->db->where('name', 'image_formats');
        $this->db->update('settings', array('value' => implode(',', $image_formats)));

        $this->db->where('name', 'video_formats');
        $this->db->update('settings', array('value' => implode(',', $video_formats)));
    }

    /**
     * Retrieve all accepted formats.
     *
     * @return [audio_formats, video_formats, image_formats]
     */
    public function formats_get_all($args = [])
    {
        $return = array();

        $this->db->where('name', 'audio_formats');
        $audio = $this->db->get_one('settings');

        $this->db->where('name', 'video_formats');
        $video = $this->db->get_one('settings');

        $this->db->where('name', 'image_formats');
        $image = $this->db->get_one('settings');

        $return['audio_formats'] = $audio['value'] ? explode(',', $audio['value']) : [];
        $return['video_formats'] = $video['value'] ? explode(',', $video['value']) : [];
        $return['image_formats'] = $image['value'] ? explode(',', $image['value']) : [];

        return $return;
    }

    /**
     * Remove all associated instances where a media item is used. Note that we
     * cannot rely on CASCADE in the database to update this for us, as the media
     * IDs will still exist when moved to the archive, but we want to remove the
     * media IDs from the associated tables anyway.
     *
     * @param id
     */
    public function remove_where_used($args = [])
    {
        OBFHelpers::require_args($args, ['id']);
        $id = $args['id'];

        // remove from player ids
        $this->db->where('media_id', $id);
        $this->db->delete('players_station_ids');

        // remove from playlists (items)
        $this->db->where('item_type', 'media');
        $this->db->where('item_id', $id);
        $this->db->delete('playlists_items');

        // delete from shows_cache where this item has been scheduled.
        $this->db->where_like('data', '"id":"' . $this->db->escape($id) . '"');
        $this->db->delete('shows_cache');

        // remove from schedules, schedules recurring
        $this->db->where('item_id', $id);
        $this->db->where('item_type', 'media');
        $this->db->delete('shows');

        // remove from alerts
        $this->db->where('item_id', $id);
        $this->db->delete('alerts');
    }

    /**
     * Check whether a given type/format is allowed and valid.
     *
     * @param type Image, audio, or video.
     * @param format
     *
     * @return is_valid
     */
    public function format_allowed($args = [])
    {
        // OBFHelpers::default($args, ['type' => '', 'format' => '']);
        $type = $args['type'];
        $format = $args['format'];

        if (empty($type) || empty($format)) {
            return false;
        }

        if ($type == 'image') {
            $this->db->where('name', 'image_formats');
            $allowed_formats = $this->db->get_one('settings');
        } elseif ($type == 'audio') {
            $this->db->where('name', 'audio_formats');
            $allowed_formats = $this->db->get_one('settings');
        } elseif ($type == 'video') {
            $this->db->where('name', 'video_formats');
            $allowed_formats = $this->db->get_one('settings');
        }

        $allowed_formats = explode(',', $allowed_formats['value']);

        return in_array($format, $allowed_formats);
    }

    /**
     * Generate a random file location (splitting files up into directories). Also
     * create directories in upload.
     *
     * @return rand_chars
     */
    public function rand_file_location($args = [])
    {
        $charSelect = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $randVal = rand(0, 1295);

        $randValA = $randVal % 36;
        $randValB = ($randVal - $randValA) / 36;

        $charA = $charSelect[$randValA];
        $charB = $charSelect[$randValB];

        $requiredDirs = array();
        $requiredDirs[] = OB_MEDIA . '/' . $charA;
        $requiredDirs[] = OB_MEDIA_ARCHIVE . '/' . $charA;
        $requiredDirs[] = OB_MEDIA_UPLOADS . '/' . $charA;

        $requiredDirs[] = OB_MEDIA . '/' . $charA . '/' . $charB;
        $requiredDirs[] = OB_MEDIA_ARCHIVE . '/' . $charA . '/' . $charB;
        $requiredDirs[] = OB_MEDIA_UPLOADS . '/' . $charA . '/' . $charB;

        $requiredDirs[] = OB_THUMBNAILS . '/media';
        $requiredDirs[] = OB_THUMBNAILS . '/media/' . $charA;
        $requiredDirs[] = OB_THUMBNAILS . '/media/' . $charA . '/' . $charB;

        foreach ($requiredDirs as $checkDir) {
            if (!file_exists($checkDir)) {
                mkdir($checkDir);
            }
        }

        return $charA . $charB;
    }

    /**
     * Retrieve ID3 tag.
     *
     * @param filename
     *
     * [artist, album, title, comments]
     */
    public function getid3($args = [])
    {
        OBFHelpers::require_args($args, ['filename']);
        $filename = $args['filename'];

        require_once('extras/getid3/getid3/getid3.php');
        $getID3 = new getID3();

        $info = $getID3->analyze($filename);
        getid3_lib::CopyTagsToComments($info);

        $id3 = $this->id3makesafe($info);

        $id3_data = array();
        if (isset($id3['comments']['artist'])) {
            $id3_data['artist'] = $id3['comments']['artist'];
        }
        if (isset($id3['comments']['album'])) {
            $id3_data['album'] = $id3['comments']['album'];
        }
        if (isset($id3['comments']['title'])) {
            $id3_data['title'] = $id3['comments']['title'];
        }
        if (isset($id3['comments']['comments'])) {
            $id3_data['comments'] = $id3['comments']['comments'];
        }

        return $id3_data;
    }

    /**
     * Make ID3 tags safe for reading.
     *
     * @param array ID3 tags array.
     *
     * @return tags
     */
    private function id3makesafe($array)
    {
        foreach (array_keys($array) as $key) {
            if (gettype($array[$key]) == "array") {
                $array[$key] = $this->id3makesafe($array[$key]);
            } elseif (gettype($array[$key]) == "string") {
                $array[$key] = @iconv('UTF-8', 'UTF-8//IGNORE', $array[$key]);
            }
        }

        return $array;
    }
}
