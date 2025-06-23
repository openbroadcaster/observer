<?php

class OBUpdate20231117 extends OBUpdate
{
    public function items()
    {
        $updates = [];

        $updates[] = "Add random file_location to playlists on insert.";

        return $updates;
    }

    public function run()
    {
        $this->db->query("
CREATE TRIGGER playlists_file_location_trigger
BEFORE INSERT ON playlists
FOR EACH ROW
BEGIN
    DECLARE randomChar CHAR(2);
    SET randomChar = CONCAT(SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(RAND() * 36) + 1, 1),
                            SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', FLOOR(RAND() * 36) + 1, 1));
    SET NEW.file_location = randomChar;
END;
        ");
        if ($this->db->error()) {
            echo $this->db->error();
            return false;
        }

        return true;
    }
}
