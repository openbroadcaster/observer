<?php

class OBUpdate20240818 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add users_nonces table for one-time authenticated GET requests.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('
            CREATE TABLE users_nonces (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                nonce VARCHAR(255) NOT NULL,
                created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
            );
        ');

        return true;
    }
}
