<?php

namespace OB\Classes\Cron;

use OB\Classes\Base\Cron;

// TODO add player monitoring emails to player settings, etc.
class PlayerNotices extends Cron
{
    public function interval(): int
    {
        return 900;
    }

    public function run(): bool
    {
        $db = \OBFDB::get_instance();

        $cutoff = strtotime('-1 hour'); // connection must be made at least once/hour. this should be a setting at some point, maybe in player settings?
        $connect_types = ['schedule','playlog','emergency'];
        foreach ($connect_types as $type) {
            $db->where('last_connect_' . $type, $cutoff, '<=');
            $players = $db->get('players');

            foreach ($players as $player) {
                $id = $player['id'];

                $db->where('player_id', $id);

                // TODO change to "player_last_connect_"; will need an updates script to change the event names in the database.
                $db->where('event', 'player_last_connect_' . $type . '_warning');
                $db->where('toggled', 0);
                $notices = $db->get('notices');

                foreach ($notices as $notice) {
                    $mailer = new \PHPMailer\PHPMailer\PHPMailer();
                    $mailer->Body = 'This is a warning that player "' . $player['name'] . '" has not connected for "' . $type . '" in the last hour.
        
Please take steps to ensure this player is functioning properly.';
                    $mailer->From = OB_EMAIL_REPLY;
                    $mailer->FromName = OB_EMAIL_FROM;
                    $mailer->Subject = 'Player Warning';
                    $mailer->AddAddress($notice['email']);
                    $mailer->Send();

                    $db->where('id', $notice['id']);
                    $db->update('notices', ['toggled' => 1]);
                }
            }
        }

        return true;
    }
}
