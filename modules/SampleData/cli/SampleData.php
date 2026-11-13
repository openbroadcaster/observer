<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OpenBroadcaster\Modules\SampleData\CLI;

use OpenBroadcaster\Base\CLI;
use OpenBroadcaster\Support\Load;
use OpenBroadcaster\Support\User;

class SampleData extends CLI
{
    protected array|string $help = [
        ['import <profile> [confirm]', 'import a profile. "confirm" allows an install with its own content'],
    ];

    public function run(array $args): bool
    {
        if (($args[0] ?? '') != 'import' || empty($args[1])) {
            echo 'Usage: ob sample data import <profile> [confirm]', PHP_EOL;
            exit(1);
        }

        // cli/ob.php does exit(! $success), which before PHP 8.4 prints "1" and exits with 0.
        if (!$this->act_as_admin() || !$this->import($args[1], ($args[2] ?? '') == 'confirm')) {
            exit(1);
        }

        return true;
    }

    private function import($profile, $confirmed)
    {
        $model = Load::get_instance()->model('SampleData', 'SampleData');

        $preflight = $model('preflight', $profile);
        if ($preflight['problem']) {
            echo "Can't import: {$preflight['problem']}", PHP_EOL;
            return false;
        }

        foreach ($preflight['steps'] as $step) {
            $offset = 0;
            do {
                $result = $model('run_step', $profile, $step, $offset, $confirmed);
                if (!$result[0]) {
                    echo "Stopped at {$step}: {$result[1]}", PHP_EOL;
                    return false;
                }

                $data = $result[2];
                foreach ($data['log'] as $line) {
                    echo $line, PHP_EOL;
                }
                if (!empty($data['details']['player_password'])) {
                    echo "Player ID: {$data['details']['player_id']}", PHP_EOL;
                    echo "Player password: {$data['details']['player_password']}", PHP_EOL;
                }
                if (!empty($data['details']['busy'])) {
                    sleep(5);
                }

                $offset = $data['next_offset'];
            } while ($offset !== null);
        }

        return true;
    }

    // imported content needs an owner, and the import is only open to administrators.
    private function act_as_admin()
    {
        $this->db->where('group_id', 1);
        $this->db->orderby('user_id');
        $admin = $this->db->get_one('users_to_groups');
        if (!$admin) {
            echo 'This install has no administrator.', PHP_EOL;
            return false;
        }

        $this->db->where('id', $admin['user_id']);
        $user = User::get_instance();
        $user->userdata = $this->db->get_one('users');
        $user->is_admin = true;

        return true;
    }
}
