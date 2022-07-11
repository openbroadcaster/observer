<?php

class OBUpdate20180531 extends OBUpdate
{
  public function items()
  {
    $updates = array();
    $updates[] = 'Store genre default for new media.';
    return $updates;
  }

  public function run()
  {
    $this->db->query('ALTER TABLE `media_genres` ADD `is_default` BOOLEAN NOT NULL DEFAULT FALSE AFTER `media_category_id`');
    return true;
  }
}
