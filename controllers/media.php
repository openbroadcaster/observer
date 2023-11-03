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
 * The media controller manages all media on the server. It also manages acceptable
 * formats, searching, versioning, and archiving for media items.
 *
 * @package Controller
 */
class Media extends OBFController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return all acceptable media formats.
     *
     * @return formats
     *
     * @route GET /v2/media/formats
     */
    public function formats_get()
    {
        $this->user->require_authenticated();
        $formats = $this->models->media('formats_get_all');
        return array(true,'Accepted formats.',$formats);
    }

    /**
     * Update the acceptable media formats table. Video, image, and audio formats
     * are set separately. Requires the 'manage_media_settings' permission.
     *
     * @param video_formats
     * @param image_formats
     * @param audio_formats
     *
     * @route POST /v2/media/formats
     */
    public function formats_save()
    {
        $this->user->require_permission('manage_media_settings');

        $data['video_formats'] = $this->data('video_formats');
        $data['image_formats'] = $this->data('image_formats');
        $data['audio_formats'] = $this->data('audio_formats');

        $validation = $this->models->media('formats_validate', ['data' => $data]);
        if ($validation[0] == false) {
            return $validation;
        }

        $this->models->media('formats_save', ['data' => $data]);

        //T Media Settings
        //T File format settings saved.
        return array(true,['Media Settings','File format settings saved.']);
    }

    /**
     * Return media search history and saved searches for currently logged in user.
     *
     * @return [saved, history]
     *
     * @route GET /v2/media/searches
     */
    public function media_my_searches()
    {
        $this->user->require_authenticated();
        return array(true,'Searches',array('saved' => $this->models->media('search_get_saved', ['type' => 'saved']), 'history' => $this->models->media('search_get_saved', ['type' => 'history'])));
    }

    /**
     * Saves an item by moving it from 'history' to 'saved' in the media searches
     * table.
     *
     * @param id
     *
     * @route POST /v2/media/searches
     */
    public function media_my_searches_save()
    {
        $this->user->require_authenticated();

        if ($this->models->media('search_save_history', ['id' => $this->data('id'), 'user_id' => $this->user->param('id')])) {
            return array(true,'Search item saved.');
        } else {
            return array(false,'Error saving search item.');
        }
    }

    /**
     * Edit a search query in the user's saved searches.
     *
     * @param id
     * @param filters
     * @param description
     *
     * @route PUT /v2/media/searches/(:id:)
     */
    public function media_my_searches_edit()
    {
        $this->user->require_authenticated();

        $id = $this->data('id');
        $filters = $this->data('filters');
        $description = trim($this->data('description'));

        if ($this->models->media('search_edit', ['id' => $id, 'filters' => $filters, 'description' => $description, 'user_id' => $this->user->param('id')])) {
            return array(true,'Search item edited.');
        } else {
            return array(false,'Error editing search item.');
        }
    }

    /**
     * Delete a search query from the user's saved searches.
     *
     * @param id
     *
     * @route DELETE /v2/media/searches/(:id:)
     */
    public function media_my_searches_delete()
    {
        $this->user->require_authenticated();

        if ($this->models->media('search_delete_saved', ['id' => $this->data('id'), 'user_id' => $this->user->param('id')])) {
            return array(true,'Search item deleted.');
        } else {
            return array(false,'Error deleting search item.');
        }
    }

    /**
     * Set a search item to be part of the default search for the current user.
     * This means that simple searches will by default include the filters specified
     * here.
     *
     * @param id
     *
     * @route POST /v2/media/searches/default
     */
    public function media_my_searches_default()
    {
        $this->user->require_authenticated();

        if ($this->models->media('search_default', ['id' => $this->data('id'), 'user_id' => $this->user->param('id')])) {
            return array(true,'Search item is now default.');
        } else {
            return array(false,'Error setting search item as default.');
        }
    }

    /**
     * Unset a default search filter for the current user.
     *
     * @param id
     *
     * @route DELETE /v2/media/searches/default/(:id:)
     */
    public function media_my_searches_unset_default()
    {
        $this->user->require_authenticated();

        if ($this->models->media('search_unset_default', ['user_id' => $this->user->param('id')])) {
            return array(true,'Search default has been unset.');
        } else {
            return array(false,'Error removing default from search item.');
        }
    }

    /**
     * Returns a boolean value determining whether the current user can edit the
     * provided media item. Private method used by a number of other methods in
     * this controller. The rules go as follows:
     *
     * If the user has the 'manage_media' permission, return TRUE. If the media item
     * is owned by the user AND the user has the 'create_own_media' permission, return
     * TRUE. If the user is in the permissions column for the specified media item,
     * return TRUE. If one of the user's groups is in the groups permissions column
     * for the specified media item, return TRUE. Otherwise, return FALSE.
     *
     * @param media The media item to check. An associative array containing information about the item, including the ID.
     *
     * @return can_edit
     */
    private function user_can_edit($media)
    {
        $permissions = $this->models->media('get_permissions', ['media_id' => $media['id']]);

        if ($this->user->check_permission('manage_media')) {
            return true;
        }
        if ($media['owner_id'] == $this->user->param('id') && $this->user->check_permission('create_own_media')) {
            return true;
        }
        if (array_search($this->user->param('id'), $permissions['users']) !== false) {
            return true;
        }
        if (count(array_intersect($this->user->get_group_ids(), $permissions['groups'])) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Search the media tables for one or more items.
     *
     * @param q Query
     * @param l Limit
     * @param o Offset
     * @param sort_by
     * @param sort_dir
     * @param s Status
     * @param my Ownership. Set to filter for media items owned by user.
     * @param save_history Save to search history if set and we're in advanced search mode.
     *
     * @return [num_results, media]
     *
     * @route GET /v2/media/search
     */
    public function search()
    {
        $this->user->require_authenticated();

        $params['query'] = $this->data('q');
        $params['limit'] = $this->data('l');
        $params['offset'] = $this->data('o');
        $params['sort_by'] = $this->data('sort_by');
        $params['sort_dir'] = $this->data('sort_dir');
        $params['status'] = $this->data('s');
        $params['my'] = $this->data('my');

        // if we're doing a simple search, we might need to apply some 'default' filters. this is handled by the media model search method.
        if ($params['query']['mode'] == 'simple' && $default_filters = $this->models->media('search_get_default_filters', ['user_id' => $this->user->param('id')])) {
            $params['default_filters'] = $default_filters;
        }

        $media_result = $this->models->media('search', ['params' => $params, 'player_id' => null]);

        if ($media_result == false) {
            return array(false,'Search error.');
        }

        if ($this->data('save_history') && $params['query']['mode'] == 'advanced') {
            $this->models->media('search_save', ['query' => $params['query']]);
        }

        foreach ($media_result[0] as &$media) {
            $media['can_edit'] = $this->user_can_edit($media);
        }

        return array(true,'Media',array('num_results' => $media_result[1],'media' => $media_result[0]));
    }

    /**
     * Edit media items. Can update more than a single item at once. User requires
     * 'create_own_media' or 'manage_media' permissions to update media items.
     * Also, 'media_advanced_permissions' is required to update any of the advanced
     * permissions fields. Media gets validated, and will only be updated if
     * validation succeeds for all provided items.

     * This method gets information from the Uploads model and makes sure to add
     * that to the media items.
     *
     * Note that the PUT request does not take an id in the URL because multiple media
     * items can be updated at the same time.
     *
     * @param media The media items to update.
     *
     * @route POST /v2/media
     * @route PUT /v2/media
     */
    public function save()
    {
        $media = $this->data('media');

        $all_valid = true;
        $validation = array();

        // Split POST and PUT when using v2 api: one for creating new media,
        // one for updating existing ones. This means id should be unset on POST,
        // while it should be required on PUT with file_id and file_key set to empty
        // strings.
        if ($this->api_version() === 2) {
            foreach ($media as $media_key => $media_item) {
                if ($this->api_request_method() === 'POST') {
                    unset($media[$media_key]['id']);

                    if (empty($media_item['file_id']) || empty($media_item['file_key'])) {
                        return [false, 'File id and key required to create new media item.'];
                    }
                } elseif ($this->api_request_method() === 'PUT') {
                    $media[$media_key]['file_id'] = '';
                    $media[$media_key]['file_key'] = '';

                    if (empty($media_item['id'])) {
                        return [false, 'Media id required to update media item.'];
                    }
                }
            }
        }

        // add our file info to our media array. (this also validates the file upload id/key)
        // also trim artist/title (which is used to determine filename)
        foreach ($media as $index => $item) {
            if (!empty($item['file_id'])) {
                $media[$index]['file_info'] = $this->models->uploads('file_info', $item['file_id'], $item['file_key']);
            }

            $media[$index]['artist'] = trim($media[$index]['artist']);
            $media[$index]['title'] = trim($media[$index]['title']);
        }

        // remove advanced permissions if we don't have permission to do that
        if (!$this->user->check_permission('media_advanced_permissions')) {
            foreach ($media as $index => $item) {
                if (isset($item['advanced_permissions_users'])) {
                    unset($media[$index]['advanced_permissions_users']);
                }
                if (isset($item['advanced_permissions_groups'])) {
                    unset($media[$index]['advanced_permissions_groups']);
                }
            }
        }

        // validate each media item
        foreach ($media as $index => $item) {
      // check permissions
            if (empty($item['id'])) {
                $this->user->require_permission('create_own_media or manage_media');
            } else {
                $this->db->where('id', $item['id']);
                $media_item = $this->db->get_one('media');

                // if user can't edit, this will trigger a permission failure in via require_permission.
                if (!$this->user_can_edit($media_item)) {
                    $this->user->require_permission('manage_media');
                }
            }

            $check_media = $this->models->media('validate', ['item' => $item]);
            if (!$check_media[0]) {
                $validation[] = $check_media;
                $all_valid = false;
            }
        }

        // if all valid, proceed with media update (or create new)
        if ($all_valid) {
            $items = array();
            foreach ($media as $item) {
                $id = $this->models->media('save', ['item' => $item]);
                $items[] = $id;

                if (isset($item['thumbnail'])) {
                    $this->models->uploads('thumbnail_save', $id, 'media', $item['thumbnail']);
                }
            }
            //T Media has been saved.
            return array(true,'Media has been saved.',$items);
        } else {
            return array(false,'Media update validation error(s).',$validation);
        }
    }

    /**
     * Checks that user can manage versions for media item. Does not return true
     * or false, but can throw a permissions error using 'require_permissions'.
     * Private method used by other version methods in this controller.
     *
     * @param media_id
     */
    private function versions_require_permission($media_id)
    {
        $media = $this->models->media('get_by_id', ['id' => $media_id]);

        // manage_media_versions permission is always required
        $this->user->require_permission('manage_media_versions');

        // if we own the media item, we also require create_own_media or manage_media
        if ($media && $media['owner_id'] == $this->user->param('id')) {
            $this->user->require_permission('create_own_media or manage_media');
        } else {
            // if we don't own the media item, we also require manage_media
            $this->user->require_permission('manage_media');
        }
    }

    /**
     * Get media versions.
     *
     * @param media_id
     *
     * @return [versions, media]
     *
     * @route GET /v2/media/versions/(:media_id:)
     */
    public function versions()
    {
        $data = [
        'media_id' => $this->data('media_id')
        ];

        $this->versions_require_permission($data['media_id']);

        $return = $this->models->media('versions', ['data' => $data]);

        // also return our media info
        if ($return[0]) {
            $return[2]['media'] = $this->models->media('get_by_id', ['id' => $data['media_id']]);
        }

        return $return;
    }

    /**
     * Add new media version file.
     *
     * @param media_id
     * @param file_id
     * @param file_key
     *
     * @route POST /v2/media/versions
     */
    public function version_add()
    {
        $data = [
        'media_id' => $this->data('media_id'),
        'file_id' => $this->data('file_id'),
        'file_key' => $this->data('file_key')
        ];

        $this->versions_require_permission($data['media_id']);

        return $this->models->media('version_add', ['data' => $data]);
    }

    /**
     * Edit media version.
     *
     * @param media_id
     * @param created Version timestamp
     * @param notes
     *
     * @route PUT /v2/media/versions/(:media_id:)/(:created:)
     */
    public function version_edit()
    {
        $data = [
        'media_id' => $this->data('media_id'),
        'created' => $this->data('created'),
        'notes' => $this->data('notes')
        ];

        $this->versions_require_permission($data['media_id']);

        return $this->models->media('version_edit', ['data' => $data]);
    }

    /**
     * Delete media version.
     *
     * @param media_id
     * @param created Version timestamp
     *
     * @route DELETE /v2/media/versions/(:media_id:)/(:created:)
     */
    public function version_delete()
    {
        $data = [
        'media_id' => $this->data('media_id'),
        'created' => $this->data('created')
        ];

        $this->versions_require_permission($data['media_id']);

        return $this->models->media('version_delete', ['data' => $data]);
    }

    /**
     * Set media active version.
     *
     * @param media_id
     * @param created Version timestamp
     *
     * @route PATCH /v2/media/versions/(:media_id:)
     */
    public function version_set()
    {
        $data = [
        'media_id' => $this->data('media_id'),
        'created' => $this->data('created')
        ];

        $this->versions_require_permission($data['media_id']);

        return $this->models->media('version_set', ['data' => $data]);
    }

    /**
     * Archive media items. Requires the 'manage_media' permission.
     *
     * @param id An array of media IDs. Can be a single ID.
     *
     * @route POST /v2/media/archive
     */
    public function archive()
    {
        $this->user->require_authenticated();

        $ids = $this->data('id');

        // if we just have a single ID, make it into an array so we can proceed on that assumption.
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        // check permissions
        foreach ($ids as $id) {
            $media_item = $this->models->media('get_by_id', ['id' => $id]);

            // if user can't edit, this will trigger a permission failure in via require_permission.
            if (!$this->user_can_edit($media_item)) {
                $this->user->require_permission('manage_media');
            }
        }

        if (!$this->models->media('archive', ['ids' => $ids])) {
            return array(false,'An error occurred while attempting to archive this media.');
        }

        return array(true,'Media has been archived.');
    }

    /**
     * Unarchive media items. Requires 'manage_media' permission.
     *
     * @param id An array of media IDs. Can be a single ID.
     *
     * @route POST /v2/media/archive/undo
     */
    public function unarchive()
    {
        $this->user->require_permission('manage_media');

        $ids = $this->data('id');

        // if we just have a single ID, make it into an array so we can proceed on that assumption.
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (!$this->models->media('unarchive', ['ids' => $ids])) {
            return array(false,'An error occurred while attempting to un-archive this media.');
        }

        return array(true,'Media has been un-archived.');
    }

    /**
     * Delete archived or unapproved media items. Requires 'manage_media' permission.
     * Will return an error if any of the media items aren't archived or unapproved.
     *
     * @param id An array of media IDs. Can be a single ID.
     *
     * @route DELETE /v2/media/archive
     */
    public function delete()
    {
        $this->user->require_permission('manage_media');

        $ids = $this->data('id');

        // if we just have a single ID, make it into an array so we can proceed on that assumption.
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (!$this->models->media('delete', ['ids' => $ids])) {
            return array(false,'An error occurred while attempting to delete this media.');
        }

        return array(true,'Media has been permanently deleted.');
    }

    /**
     * Get a single media item.
     *
     * @param id
     *
     * @return media
     *
     * @route GET /v2/media/(:id:)
     */
    public function get()
    {
        $this->user->require_authenticated();

        $id = $this->data('id');

        $media = $this->models->media('get_by_id', ['id' => $id]);

        //T Media not found.
        if (!$media) {
            return array(false,'Media not found.');
        }

        if ($media['status'] == 'private' && $media['owner_id'] != $this->user->param('id')) {
            $this->user->require_permission('manage_media');
        }

        if ($this->user->check_permission('media_advanced_permissions')) {
            $permissions = $this->models->media('get_permissions', ['media_id' => $id]);
            $media['permissions_users'] = $permissions['users'];
            $media['permissions_groups'] = $permissions['groups'];
        }

        $media['can_edit'] = $this->user_can_edit($media);

        // get where used information...
        if ($this->data('where_used')) {
            $where_used = $this->models->media('where_used', ['id' => $id, 'include_dynamic' => true]);
            $media['where_used'] = $where_used;
        }

        // get thumbnail, if one exists
        $thumbnail = $this->models->uploads('thumbnail_get', $id, 'media');
        if ($thumbnail[0]) {
            $media['thumbnail'] = $thumbnail[1];
        }

        return array(true,'Media data.',$media);
    }
}
