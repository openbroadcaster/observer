<?php

class OBUpdate20141121 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Updates to users table.';
        $updates[] = 'User settings table.';
        // $updates[] = 'Translation Framework';
        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE users DROP COLUMN about");

        $this->db->query("CREATE TABLE IF NOT EXISTS `users_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `setting` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");

        //$this->db->query("ALTER TABLE  `users` ADD  `language` VARCHAR( 255 ) NOT NULL DEFAULT  'default'");

        return true;
    }
}
