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
 * Secondary model for managing media categories.
 *
 * @package Model
 */
class MediaCategoriesModel extends OBFModel
{
    /**
     * Search media categories.
     *
     * @param filters
     * @param orderby
     * @param orderdesc
     * @param limit
     * @param offset
     *
     * @return [id, name]
     */

    public function search($filters, $orderby, $orderdesc, $limit, $offset)
    {
        $this->db->what('media_categories.id', 'id');
        $this->db->what('media_categories.name', 'name');

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

        $result = $this->db->get('media_categories');

        // set default category
        $current_default = $this->get_default();
        foreach ($result as &$category) {
            if ($category['id']==$current_default) {
                $category['is_default']=1;
            } else {
                $category['is_default']=0;
            }
        }

        return $result;
    }

    /**
     * Save a media category.
     *
     * @param data
     * @param id Optional. Specified when editing a pre-existing category.
     *
     * @return id
     */
    public function save($data, $id=false)
    {
        $set_default = $data['is_default']==1;
        unset($data['is_default']);

        if (empty($id)) {
            $id = $this->db->insert('media_categories', $data);
        } else {
            if (!$this->db->id_exists('media_categories', $id)) {
                return false;
            }
            $this->db->where('id', $id);
            $this->db->update('media_categories', $data);
        }

        $current_default = $this->get_default();

        // set default value if necessary
        if ($set_default) {
            // remove any existing default
            $this->db->where('name', 'media_category_default');
            $this->db->delete('settings');

            // set this as default
            $this->db->insert('settings', ['name'=>'media_category_default','value'=>$id]);
        }

        // remove default value if necessary
        elseif ($current_default==$id) {
            $this->db->where('name', 'media_category_default');
            $this->db->delete('settings');
        }

        return $id;
    }

    /**
     * Get the default media category. Returns FALSE if no default category is set.
     *
     * @return default_category
     */
    public function get_default()
    {
        $this->db->where('name', 'media_category_default');
        $setting = $this->db->get_one('settings');
        if ($setting) {
            return $setting['value'];
        } else {
            return false;
        }
    }

    /**
     * Validate a category before updating or inserting.
     *
     * @param data
     * @param id Optional. Specified when updating an existing category.
     *
     * @return is_valid
     */
    public function validate($data, $id=false)
    {
        //T A category name is required.
        if (!$data['name']) {
            return array(false,['Category Edit','A category name is required.']);
        }
        return array(true,'Valid.');
    }

    /**
     * Check whether a category can be deleted. Makes sure that all genres within
     * the category are deleted first.
     *
     * @param id
     *
     * @return is_deletable
     */
    public function can_delete($id)
    {
        $this->db->where('media_category_id', $id);
        $genres = $this->db->get('media_genres');

        //T You must remove the genres within this category before deleting the category.
        if (count($genres)>0) {
            return array(false,['Category Edit','You must remove the genres within this category before deleting the category.']);
        } else {
            return array(true,'Can delete.');
        }
    }

    /**
     * Delete a category.
     *
     * @param id
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete('media_categories');

        return $delete;
    }

    /**
     * Get the category name and whether or not it's the default category by ID.
     *
     * @param id
     *
     * @return [id, name, is_default]
     */
    public function get_by_id($id)
    {
        $this->db->where('media_categories.id', $id);
        $this->db->what('media_categories.id', 'id');
        $this->db->what('media_categories.name', 'name');

        $category = $this->db->get_one('media_categories');

        if ($category['id']==$this->get_default()) {
            $category['is_default'] = 1;
        } else {
            $category['is_default'] = 0;
        }

        return $category;
    }
}
