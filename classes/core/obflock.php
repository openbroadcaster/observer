<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * OpenBroadcaster acquire or release a lock.
 *
 * @package Class
 */
class OBFlock
{
    private string $filename;
    private $fp;
    private bool $locked = false;

    public function __construct(string $name)
    {
        $this->filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'openbroadcaster-lock-' . md5($name) . '.lock';
    }

    public function acquire(): bool
    {
        // already locked
        if ($this->locked) {
            return true;
        }

        // open file
        $this->fp = fopen($this->filename, 'w');
        if ($this->fp === false) {
            return false; // Could not open file
        }

        // lock file
        if (!flock($this->fp, LOCK_EX | LOCK_NB)) {
            fclose($this->fp);
            $this->fb = null;
            return false;
        }

        // sucecss!
        $this->locked = true;
        return true;
    }

    public function release(): bool
    {
        // if locked, try unlocking.
        if ($this->locked && flock($this->fp, LOCK_UN)) {
            fclose($this->fp);
            @unlink($this->filename);
            $this->locked = false;
            $this->fp = null;

            // success!
            return true;
        }

        // not locked, or could not unlock.
        return false;
    }

    // release lock on destruct
    public function __destruct()
    {
        $this->release();
    }
}
