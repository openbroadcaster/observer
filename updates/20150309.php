<?php

class OBUpdate20150309 extends OBUpdate
{
  public function items()
  {
    $updates = array();
    $updates[] = 'Support line-in scheduling.';
    return $updates;
  }

  public function run()
  {

    $this->db->query("ALTER TABLE  `devices` ADD  `support_linein` BOOLEAN NOT NULL DEFAULT  '0' AFTER  `support_images`");
    $this->db->query("ALTER TABLE  `schedules_recurring` CHANGE  `item_type`  `item_type` ENUM(  'media',  'playlist',  'linein' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");
    $this->db->query("ALTER TABLE  `schedules` CHANGE  `item_type`  `item_type` ENUM(  'media',  'playlist',  'linein' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");

    return true;

  }
}
