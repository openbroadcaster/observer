<?php

class OBUpdate20200630 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Add sessions table for user logins, allowing users to be logged in in multiple locations at once.";
        $updates[] = "Delete session column from users table. No need to move them as sessions will rely on session row ID rather than user ID from this point on.";
        return $updates;
    }

    public function run()
    {
        // Add sessions table for user logins, allowing users to be logged in in multiple locations at once.
        $this->db->query('CREATE TABLE `users_sessions` (
      `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
      `user_id` INT(10) UNSIGNED NOT NULL ,
      `key` VARCHAR(255) NOT NULL ,
      `key_expiry` INT(10) UNSIGNED NOT NULL ,
      PRIMARY KEY (`id`), INDEX (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        $this->db->query('ALTER TABLE `users_sessions`
      ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // Delete session column from users table. No need to move them as sessions will rely on session row ID rather than user ID from this point on.
        /* $users = $this->db->get('users');
        foreach ($users as $user) {
          $this->db->insert('users_sessions', [
            'user_id'    => $user['id'],
            'key'        => $user['key'],
            'key_expiry' => $user['key_expiry']
          ]);
        } */

        $this->db->query('ALTER TABLE `users` DROP `key`, DROP `key_expiry`;');

        return true;
    }
}
