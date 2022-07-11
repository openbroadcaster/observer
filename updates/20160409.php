<?php

class OBUpdate20160409 extends OBUpdate
{
  public function items()
  {
    $updates = array();
    $updates[] = 'Security updates. Users will be required to reset their password after this update.';
    $updates[] = 'Device password will be reset the next time each device connects. Manually reset passwords for any devices that do not connect regularly.';
    return $updates;
  }

  public function run()
  {

    // increase password column length
    $this->db->query('ALTER TABLE `users` MODIFY `password` VARCHAR(255)');
    $this->db->query('ALTER TABLE `devices` MODIFY `password` VARCHAR(255)');

    // get rid of any insecure password hashes (users will be required to reset their password)
    $users = $this->db->get('users');

    foreach($users as $user)
    {
      $info = password_get_info($user['password']);

      if($info['algo']==0)
      {
        $this->db->where('id', $user['id']);
        $this->db->update('users', array('password'=>''));
      }
    }

    return true;

  }

 // ALTER TABLE `users` MODIFY `password` VARCHAR(255);

}
