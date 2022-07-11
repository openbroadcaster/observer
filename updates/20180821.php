<?php

class OBUpdate20180821 extends OBUpdate
{
  public function items()
  {
    $updates = array();
    $updates[] = 'Copyright and public download permissions.';
    return $updates;
  }

  public function run()
  {
    $this->db->query("INSERT INTO `users_permissions` (`id`, `name`, `description`, `category`) VALUES (NULL, 'copyright_own_media', 'claim copyright on own media', 'media');");
    $this->db->query("INSERT INTO `users_permissions` (`id`, `name`, `description`, `category`) VALUES (NULL, 'allow_copyright_public', 'allow copyright holders to make own media public', 'media'), (NULL, 'allow_noncopyright_public', 'allow non-copyright holders to make own media public', 'media');");
    $this->db->query("ALTER TABLE `media` ADD `is_public` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_copyright_owner`;");
    return true;
  }
}
