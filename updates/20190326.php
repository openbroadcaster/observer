<?php

class OBUpdate20190326 extends OBUpdate
{
  public function items()
  {
    $updates = array();
    $updates[] = 'Support longer setting values (text column).';
    return $updates;
  }

  public function run()
  {
    $this->db->query("ALTER TABLE `settings` CHANGE `value` `value` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;");
    return true;
  }
}
