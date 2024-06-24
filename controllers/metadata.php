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
 * Manages metadata.
 *
 * @package Controller
 */
class Metadata extends OBFController
{
    public $media_types = array('audio','image','video');

    public function __construct()
    {
        parent::__construct();
        $this->user->require_authenticated();
    }

    /**
     * Change metadata field order. Requires 'manage_media_settings' permission.
     *
     * @param order
     *
     * @route PUT /v2/metadata/order
     */
    public function metadata_order()
    {
        $this->user->require_permission('manage_media_settings');
        $this->models->mediametadata('save_field_order', $this->data('order'));
        //T Metadata field order saved.
        return [true,'Metadata field order saved.'];
    }

    /**
     * Add or edit a metadata field. Requires 'manage_media_settings' permission.
     *
     * @param id Optional when editing already existing metadata field.
     * @param name
     * @param description
     * @param type Text (single or multiple lines), boolean, dropdown, tags.
     * @param select_options Options in dropdown when selected as type.
     * @param mode Whether metadata field is required, optional, or hidden (usable via API).
     * @param default
     * @param tag_suggestions
     *
     * @route POST /v2/metadata
     * @route PUT /v2/metadata/(:id:)
     */
    public function metadata_save()
    {
        $this->user->require_permission('manage_media_settings');

        $id = (int) $this->data('id');

        if ($this->api_version() === 2) {
            if ($this->api_request_method() === 'POST') {
                $id = null;
            }
        }

        $data = [];
        $data['name'] = trim(strtolower($this->data('name')));
        $data['description'] = trim($this->data('description'));
        $data['type'] = trim($this->data('type'));
        $data['select_options'] = trim($this->data('select_options'));
        $data['mode'] = trim($this->data('mode'));
        $data['id3_key'] = trim($this->data('id3_key'));

        $data['default'] = $this->data('default');
        if (is_array($data['default'])) {
            $data['default'] = array_map('trim', $data['default']);
        } else {
            $data['default'] = trim($data['default']);
        }

        $data['tag_suggestions'] = $this->data('tag_suggestions');
        if (is_array($data['tag_suggestions'])) {
            $data['tag_suggestions'] = array_map('trim', $data['tag_suggestions']);
        } else {
            $data['tag_suggestions'] = [];
        }

        $validation = $this->models->mediametadata('validate', $data, $id);
        if ($validation[0] == false) {
            return $validation;
        }

        $save = $this->models->mediametadata('save', $data, $id);

        if (!$save) {
            return [false,'An unknown error occurred while trying to save this metadata field.'];
        } else {
            return [true,'Metadata field saved.'];
        }
    }

    /**
     * Delete a metadata field. Requires 'manage_media_settings' permission.
     *
     * @param id
     *
     * @route DELETE /v2/metadata/(:id:)
     */
    public function metadata_delete()
    {
        $this->user->require_permission('manage_media_settings');

        $delete = $this->models->mediametadata('delete', $this->data('id'));

        if (!$delete) {
            return [false,'An unknown error occured while trying to delete this metadata field.'];
        } else {
            return [true,'Metadata field deleted.'];
        }
    }

    /**
     * Search metadata field for tags from the suggested tags saved.
     *
     * @param id
     * @param search
     *
     * @return [tag]
     *
     * @route GET /v2/metadata/tags
     */
    public function metadata_tag_search()
    {
        $results = $this->models->mediametadata('tag_search', [
        'id' => $this->data('id'),
        'search' => $this->data('search')
        ]);
        return [true,'Tag search.',$results];
    }

    ////////
    // MEDIA
    ////////

    /**
     * List all media metadata fields.
     *
     * @return metadata_fields
     *
     * @route GET /v2/metadata
     */
    public function media_metadata_fields()
    {
        $fields = $this->models->mediametadata('get_all');
        return array(true,'Media metadata fields.',$fields);
    }

    /**
     * List all media core metadata fields defined in the settings table.
     *
     * @return metadata_fields
     *
     * @route GET /v2/metadata/core
     */
    public function media_get_fields()
    {
        return $this->models->mediametadata('get_fields');
    }

    /**
     * Update required metadata fields for media. Requires 'manage_media_settings'
     * permission.
     *
     * @param artist
     * @param album
     * @param year
     * @param category_id
     * @param country
     * @param language
     * @param comments
     * @param dynamic_content_default
     * @param dynamic_content_hidden
     *
     * @route PUT /v2/metadata/required
     */
    public function media_required_fields()
    {
        $this->user->require_permission('manage_media_settings');
        $result = [false, 'An unknown error occurred while trying to update required media fields.'];

        $result = $this->models->mediametadata('validate_fields', $this->data);
        if (!$result[0]) {
            return $result;
        }

        $data = array(
        'artist'                  => $this->data['artist'],
        'album'                   => $this->data['album'],
        'year'                    => $this->data['year'],
        'category_id'             => $this->data['category_id'],
        'country'                 => $this->data['country'],
        'language'                => $this->data['language'],
        'comments'                => $this->data['comments'],
        'dynamic_content_default' => $this->data['dynamic_content_default'],
        'dynamic_content_hidden'  => $this->data['dynamic_content_hidden']
        );

        $result = $this->models->mediametadata('required_fields', $data);

        return $result;
    }

    /**
     * Set the default values for metadata when saving a recording.
     *
     * @param fields An array with the keys responding to metadata fields, and the values to their default values.
     *
     * @route POST /v2/metadata/recording
     */
    public function recording_default_values_save()
    {
        $this->user->require_permission('manage_media_settings');

        $coreFields = $this->models->mediametadata('get_fields')[2];
        $customFields = $this->models->mediametadata('get_all');

        if ($coreFields['album'] === 'required' && empty($this->data['album'])) {
            //T No default album provided.
            return [false, 'No default album provided.'];
        }

        if ($coreFields['year'] === 'required' && ! ctype_digit($this->data['year'])) {
            //T No default year provided.
            return [false, 'No default year provided.'];
        }

        $genre = $this->models->mediagenres('get_by_id', $this->data['genre'] ?? 0);
        $category = $genre['media_category_id'] === $this->data['category'];
        if ($coreFields['category_id'] === 'required' && (! $genre || ! $category)) {
            //T No default category provided.
            return [false, 'No default category provided.'];
        }

        $country = $this->models->mediacountries('get_by_id', $this->data['country'] ?? 0);
        if ($coreFields['country'] === 'required' && ! $country) {
            //T No default country provided.
            return [false, 'No default country provided.'];
        }

        $language = $this->models->medialanguages('get_by_id', $this->data['language'] ?? 0);
        if ($coreFields['language'] === 'required' && ! $language) {
            //T No default language provided.
            return [false, 'No default language provided.'];
        }

        if ($coreFields['comments'] === 'required' && empty($this->data['comments'])) {
            //T No default comments provided.
            return [false, 'No default comments provided.'];
        }

        foreach ($customFields as $field) {
            if (isset($field['settings']->mode) && $field['settings']->mode === 'required') {
                $value = $this->data['custom_metadata'][$field['name']] ?? null;

                if ($value === '' || $value === null) {
                    return [false, 'No default value provided for required custom field `' . $field['name'] . '`'];
                }

                if (
                    $field['type'] === 'select' &&
                    (! in_array($value, $field['settings']->options) &&
                    (! ctype_digit($value) || count($field['settings']->options) < intval($value) || intval($value) < 0))
                ) {
                    return [false, 'Selected value not in allowed options for custom field `' . $field['name'] . '`'];
                }

                if ($field['type'] === 'integer' && ! ctype_digit($value)) {
                    return [false, 'Value for custom field `' . $field['name'] . '` must be an integer'];
                }
            }
        }

        $this->models->settings('setting_set', 'recording_defaults', json_encode($this->data));
        return [true, 'Successfully saved default recording metadata values.'];
    }

    /**
     * Get the default values for metadata when saving a recording.
     *
     * @return metadata_values
     *
     * @route GET /v2/metadata/recording
     */
    public function recording_default_values()
    {
        $defaults = $this->models->settings('setting_get', 'recording_defaults');
        if (! $defaults[0]) {
            return [false, 'No recording defaults found'];
        } else {
            return [true, 'Recording defaults', json_decode($defaults[2], true)];
        }
    }

    public function playlist_item_types()
    {
        $types = $this->models->playlists('get_item_types');
        return [true,'Playlist Item Types',$types];
    }

    /////////////
    // CATEGORIES
    /////////////

    /**
     * Return filtered and ordered media categories.
     *
     * @param filters
     * @param orderby
     * @param orderdesc
     * @param limit
     * @param offset
     *
     * @return categories
     *
     * @route GET /v2/metadata/categories
     */
    public function category_list()
    {
        $filters = $this->data('filters');
        $orderby = $this->data('orderby');
        $orderdesc = $this->data('orderdesc');
        $limit = $this->data('limit');
        $offset = $this->data('offset');

        $categories = $this->models->mediacategories('search', $filters, $orderby, $orderdesc, $limit, $offset);

        if ($categories === false) {
            return array(false,'An unknown error occurred while fetching categories.');
        } else {
            return array(true,'Category list.',$categories);
        }
    }

    /**
     * Save a media category. Requires 'manage_media_settings' permission.
     *
     * @param id Optional when editing already existing category.
     * @param name
     * @param default Set as default category for new media.
     *
     * @route POST /v2/metadata/categories
     * @route PUT /v2/metadata/categories/(:id:)
     */
    public function category_save()
    {
        $this->user->require_permission('manage_media_settings');

        $id = trim($this->data['id']);

        if ($this->api_version() === 2) {
            if ($this->api_request_method() === 'POST') {
                $id = null;
            }
        }

        $data = array();
        $data['name'] = trim($this->data('name'));
        $data['is_default'] = $this->data('default');

        $validation = $this->models->mediacategories('validate', $data, $id);
        if ($validation[0] == false) {
            return $validation;
        }

        $save = $this->models->mediacategories('save', $data, $id);

        if (!$save) {
            return array(false,'An unknown error occurred while trying to save this category.');
        } else {
            return array(true,'Category saved.');
        }
    }

    /**
     * Delete a media category. Requires 'manage_media_settings' permission.
     *
     * @param id
     *
     * @route DELETE /v2/metadata/categories/(:id:)
     */
    public function category_delete()
    {
        $this->user->require_permission('manage_media_settings');

        $id = trim($this->data['id']);

        $can_delete = $this->models->mediacategories('can_delete', $id);
        if ($can_delete[0] == false) {
            return $can_delete;
        }

        $delete = $this->models->mediacategories('delete', $id);

        if ($delete) {
            return array(true,'Category deleted.');
        } else {
            return array(false,'An unknown error occured while trying to delete the category.');
        }
    }

    /**
     * Retrieve a media category by ID.
     *
     * @param id
     *
     * @return [id, name, is_default]
     *
     * @route GET /v2/metadata/categories/(:id:)
     */
    public function category_get()
    {
        $id = trim($this->data['id']);

        $category = $this->models->mediacategories('get_by_id', $id);

        if ($category) {
            return array(true,'Category information.',$category);
        } else {
            return array(false,'Category not found.');
        }
    }

    /////////
    // GENRES
    /////////

    /**
     * Return filtered and ordered media genres.
     *
     * @param filters
     * @param orderby
     * @param orderdesc
     * @param limit
     * @param offset
     *
     * @return genres
     *
     * @route GET /v2/metadata/genres
     */
    public function genre_list()
    {
        $filters = $this->data('filters');
        $orderby = $this->data('orderby');
        $orderdesc = $this->data('orderdesc');
        $limit = $this->data('limit');
        $offset = $this->data('offset');

        $genres = $this->models->mediagenres('search', $filters, $orderby, $orderdesc, $limit, $offset);

        if ($genres === false) {
            return array(false,'An unknown error occurred while fetching genres.');
        } else {
            return array(true,'Genre list.',$genres);
        }
    }

    /**
     * Save a media genre. Requires 'manage_media_settings' permission.
     *
     * @param id Optional when updating a pre-existing genre.
     * @param name
     * @param description
     * @param media_category_id
     * @param default Set as default genre for new media.
     *
     * @route POST /v2/metadata/genres
     * @route PUT /v2/metadata/genres/(:id:)
     */
    public function genre_save()
    {
        $this->user->require_permission('manage_media_settings');

        $data = array();
        $id = trim($this->data['id']);

        if ($this->api_version() === 2) {
            if ($this->api_request_method() === 'POST') {
                $id = null;
            }
        }

        $data['name'] = $this->data('name');
        $data['description'] = $this->data('description');
        $data['media_category_id'] = $this->data('media_category_id');
        $data['is_default'] = $this->data('default');

        $validation = $this->models->mediagenres('validate', $data, $id);
        if ($validation[0] == false) {
            return $validation;
        }

        if ($this->models->mediagenres('save', $data, $id)) {
            return array(true,'Genre saved.');
        } else {
            return array(false,'An unknown error occurred while trying to save this genre.');
        }
    }

    /**
     * Delete a media genre. Requires 'manage_media_settings' permission.
     *
     * @param id
     *
     * @route DELETE /v2/metadata/genres/(:id:)
     */
    public function genre_delete()
    {
        $this->user->require_permission('manage_media_settings');

        $id = trim($this->data['id']);

        $delete = $this->models->mediagenres('delete', $id);

        if ($delete) {
            return array(true,'Genre deleted.');
        } else {
            return array(false,'An unknown error occured while trying to delete the genre.');
        }
    }

    /**
     * Return a genre by ID.
     *
     * @param id
     *
     * @return [id, name, description, media_category_id]
     *
     * @route GET /v2/metadata/genres/(:id:)
     */
    public function genre_get()
    {
        $id = trim($this->data['id']);

        $genre = $this->models->mediagenres('get_by_id', $id);

        if ($genre) {
            return array(true,'Genre information.',$genre);
        } else {
            return array(false,'Genre not found.');
        }
    }

    ///////////////
    // LOCALIZATION
    ///////////////

    /**
     * List all media countries.
     *
     * @return countries
     *
     * @route GET /v2/metadata/countries
     */
    public function country_list()
    {
        $types = $this->models->mediacountries('get_all');

        if ($types === false) {
            return array(false,'An unknown error occured while fetching countries.');
        } else {
            return array(true,'Country list.',$types);
        }
    }

    /**
     * List all media languages.
     *
     * @return languages.
     *
     * @route GET /v2/metadata/languages
     */
    public function language_list()
    {
        if (!defined('OB_SHOW_ALL_LANGUAGES') || OB_SHOW_ALL_LANGUAGES !== true) {
            // limited to individual living languages
            $languages = $this->models->medialanguages('get_main');
        } else {
            // all languages
            $languages = $this->models->medialanguages('get_all');
        }

        if ($languages === false) {
            return array(false,'An unknown error occured while fetching languages.');
        }

        // get top languages, add popularity to the languages
        $top_languages = $this->models->medialanguages('get_top');
        $language_popularities = [];
        foreach ($top_languages as $index => $top_language) {
            $language_popularities[$top_language] = $index;
        }

        foreach ($languages as &$language) {
            $language['popularity'] = $language_popularities[$language['language_id']] ?? null;
        }

        return array(true,'Language list.',$languages);
    }
}
