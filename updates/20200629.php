<?php

class OBUpdate20200629 extends OBUpdate
{
  public function items() {
    $updates   = array();
    $updates[] = "Add API key rows for creation date and last accessed.";
    return $updates;
  }

  public function run() {
    $this->db->query('ALTER TABLE `users_appkeys`
      ADD `created` INT(10) UNSIGNED NOT NULL AFTER `key`,
      ADD `last_access` INT(10) UNSIGNED NOT NULL AFTER `created`;');

    return true;
  }
}
