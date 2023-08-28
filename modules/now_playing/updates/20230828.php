<?php

class NowPlayingUpdate20230828 extends OBUpdate
{
    public function items()
    {
        $updates = ['This is an example module update.'];

        return $updates;
    }

    public function run()
    {
        return true;
    }
}