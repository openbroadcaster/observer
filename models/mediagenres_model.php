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
 * Secondary model for managing media genres.
 *
 * @package Model
 */
class MediaGenresModel extends OBFModel
{
    /**
     * Search media genres.
     *
     * @param filters
     * @param orderby
     * @param orderdesc
     * @param limit
     * @param offset
     *
     * @return genres
     */
    public function search($filters, $orderby, $orderdesc, $limit, $offset)
    {
        $this->db->what('media_genres.id', 'id');
        $this->db->what('media_genres.name', 'name');
        $this->db->what('media_genres.description', 'description');
        $this->db->what('media_genres.media_category_id', 'media_category_id');
        $this->db->what('media_categories.name', 'media_category_name');
        $this->db->what('media_genres.is_default', 'is_default');

        $this->db->leftjoin('media_categories', 'media_genres.media_category_id', 'media_categories.id');

        if ($filters) {
            foreach ($filters as $filter) {
                $column = $filter['column'];
                $value = $filter['value'];
                $operator = (empty($filter['operator']) ? '=' : $filter['operator']);

                $this->db->where($column, $value, $operator);
            }
        }

        if ($orderby) {
            $this->db->orderby($orderby, (!empty($orderdesc) ? 'desc' : 'asc'));
        } else {
            $this->db->orderby('name', 'asc');
        }

        if ($limit) {
            $this->db->limit($limit);
        }
        if ($offset) {
            $this->db->offset($offset);
        }

        $result = $this->db->get('media_genres');

        return $result;
    }

    /**
     * Save a media genre.
     *
     * @param data
     * @param id Optional. Specified when updating a pre-existing genre.
     */
    public function save($data, $id=false)
    {
        // handle default: if setting default, unset other defaults for this category.
        if ($data['is_default']!=1) {
            $data['is_default'] = 0;
        } else {
            $this->db->where('media_category_id', $data['media_category_id']);
            $this->db->update('media_genres', ['is_default'=>0]);
        }

        if (empty($id)) {
            return $this->db->insert('media_genres', $data);
        } else {
            $this->db->where('id', $id);
            $this->db->update('media_genres', $data);

            // changing the parent category of an existing genre.  update media items table appropriately.
            // note - fix at some point - media category column is redundant.
            if (!empty($data['media_category_id'])) {
                $this->db->query('update media set category_id = "'.$this->db->escape($data['media_category_id']).'" where genre_id = "'.$this->db->escape($id).'"');
            }

            return true;
        }
    }

    /**
     * Validate a genre before updating.
     *
    * @param data
    * @param id Optional. Specified when updating a pre-existing genre.
    *
    * @return is_valid
    */
    public function validate($data, $id=false)
    {
        //T A genre name is required.
        if (!$data['name']) {
            return array(false,['Genre Edit','A genre name is required.']);
        }
        if (!$this->db->id_exists('media_categories', $data['media_category_id'])) {
            return array(false,'The category ID is invalid.');
        }

        return array(true,'Valid.');
    }

    /**
     * Delete a genre.
     *
     * @param id
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete('media_genres');

        return $delete;
    }

    /**
     * Get information about a genre by ID.
     *
     * @param id
     *
     * @return [id, name, description, media_category_id]
     */
    public function get_by_id($id)
    {
        $this->db->where('media_genres.id', $id);
        $this->db->what('media_genres.id', 'id');
        $this->db->what('media_genres.name', 'name');
        $this->db->what('media_genres.description', 'description');
        $this->db->what('media_genres.media_category_id', 'media_category_id');

        $genre = $this->db->get_one('media_genres');

        return $genre;
    }
}
