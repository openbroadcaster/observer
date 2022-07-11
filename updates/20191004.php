<?php

class OBUpdate20191004 extends OBUpdate
{
  public function items() {
    $updates   = array();
    $updates[] = 'Fix media comments field character encoding.';

    return $updates;
  }

  public function run() {
    $this->db->query('ALTER TABLE `media` CHANGE `comments` `comments` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');
    return true;
  }
}
