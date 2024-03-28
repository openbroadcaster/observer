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
 * Secondary model for managing media languages.
 *
 * @package Model
 */
class MediaLanguagesModel extends OBFModel
{
    /**
     * Get all media languages.
     *
     * @return languages
     */
    public function get_all()
    {
        $this->db->orderby('ref_name');
        $types = $this->db->get('languages');

        return $types;
    }

    /**
     * Get main languages only.
     * 
     * @return languages
     */
    public function get_main()
    {
        $this->db->query('
          SELECT * FROM `languages`
          WHERE
            (`id` LIKE "%q" AND `language_type` = "S") OR
            (
	      /* `part2t` IS NOT NULL AND `part2t` != "" AND  */
              `scope` = "I" AND `language_type` = "L"
            ) 
          ORDER BY `ref_name`
        ');
        $types = $this->db->assoc_list();

        return $types;
    }
}
