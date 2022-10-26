<?php

class OBUpdate20221026 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = 'Add table for user data storage.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('CREATE TABLE `users_storage` ( '
          . '`id` int(10) unsigned NOT NULL AUTO_INCREMENT, '
          . '`user_id` int(10) unsigned NOT NULL, '
          . '`name` varchar(255) NOT NULL, '
          . '`value` text NOT NULL, '
          . 'PRIMARY KEY (`id`), '
          . 'KEY `user_id` (`user_id`), '
          . 'CONSTRAINT `users_storage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE '
          . ') ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}
