<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Group and user permissions model.
 *
 * @package Model
 */
class PermissionsModel extends OBFModel
{
    public $permission_cache = false;

    /**
     * Get group permissions from group ID.
     *
     * @param id
     *
     * @return permissions
     */
    public function get_group_permissions($id)
    {
        $r = [];

        // special handling for admin group.  admins get all permissions.
        if ($id == 1) {
            $this->db->where('enabled', 1);
            $permissions = $this->db->get('users_permissions');
            foreach ($permissions as $permission) {
                $r[] = $permission['name'];
            }
            return $r;
        }

        // handling for non-admin groups..
        $this->db->what('users_permissions_to_groups.item_id', 'item_id');
        $this->db->what('users_permissions.name', 'name');
        $this->db->leftjoin('users_permissions', 'users_permissions_to_groups.permission_id', 'users_permissions.id');
        $this->db->where('users_permissions_to_groups.group_id', $id);
        $this->db->where('users_permissions.enabled', 1);

        $permissions = $this->db->get('users_permissions_to_groups');

        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                $r[] = $permission['name'] . ($permission['item_id'] ? ':' . $permission['item_id'] : '');
            }
        }

        return $r;
    }

    /**
     * Determine the permissions for the user ID. This is the most liberal set of
     * permissions when combined with the groups they are in.
     *
     * @param id
     *
     * @return permissions
     */
    public function get_user_permissions($id)
    {
        if (!isset($this->permission_cache[$id])) {
            $this->db->what('group_id');
            $this->db->where('user_id', $id);
            $groups = $this->db->get('users_to_groups');

            // everyone should be considered part of base (new user, no assigned groups)...
            $groups[] = ['group_id' => 0];

            $result = [];

            foreach ($groups as $group) {
                $p = $this('get_group_permissions', $group['group_id']);
                foreach ($p as $pname) {
                    $result[] = $pname;
                }
            }

            $this->permission_cache[$id] = $result;
        }


        return $this->permission_cache[$id];
    }

    /**
     * Get names of groups user ID is in.
     *
     * @param id
     *
     * @return group_names
     */
    public function get_user_groups($id)
    {
        $this->db->what('users_groups.name', 'name');
        $this->db->where('users_to_groups.user_id', $id);
        $this->db->leftjoin('users_groups', 'users_to_groups.group_id', 'users_groups.id');
        $groups = $this->db->get('users_to_groups');

        $return = [];

        if ($groups) {
            foreach ($groups as $group) {
                $return[] = $group['name'];
            }
        }

        return $return;
    }

    /**
     * Check if user has a permission. Return the first found permission or FALSE
     * if none can be found.
     *
     * @param permission
     * @param userid
     *
     * @return permission
     */
    public function check_permission($permission, $userid)
    {
        $p = $this('get_user_permissions', $userid);

        $permission_array = explode(' or ', $permission);

        foreach ($permission_array as $check_permission) {
            // if we are looking for an item specific permission, then we will also accept the permission without the item id specified.
            // in this case the permission is valid for all items.
            $check_permission_array = explode(':', $check_permission);
            if (count($check_permission_array) > 1) {
                if (array_search($check_permission_array[0], $p) !== false) {
                    return true;
                }

                // in this case we will accept any item ID.
                if ($check_permission_array[1] == '*') {
                    foreach ($p as $pname) {
                        $pname_array = explode(':', $pname);
                        if ($pname_array[0] == $check_permission_array[0]) {
                            return true;
                        }
                    }
                }
            }

            // check regular permission or item-specific permission.  (permission as specified)
            if (array_search($check_permission, $p) !== false) {
                return true;
            }
        }

        return false;
    }
}
