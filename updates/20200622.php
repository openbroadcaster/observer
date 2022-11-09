<?php

class OBUpdate20200622 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = "Add API key table linked to users for requests from external sources.";
        $updates[] = "Add API key management permission to users_permissions table.";

        return $updates;
    }

    public function run()
    {
        // Add table for API key management.
        $this->db->query('CREATE TABLE `users_appkeys` (
      `id` int(10) UNSIGNED NOT NULL,
      `user_id` int(10) UNSIGNED NOT NULL,
      `name` varchar(255) NOT NULL,
      `key` varchar(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        $this->db->query('ALTER TABLE `users_appkeys`
      ADD PRIMARY KEY (`id`),
      ADD KEY `user_id` (`user_id`);');

        $this->db->query('ALTER TABLE `users_appkeys`
      MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;');

        $this->db->query('ALTER TABLE `users_appkeys`
      ADD CONSTRAINT `users_api_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // Add to permissions table.
        $this->db->insert('users_permissions', [
        'name'        => 'manage_appkeys',
        'description' => 'create, edit, and delete own app keys',
        'category'    => 'administration'
        ]);

        return true;
    }
}
