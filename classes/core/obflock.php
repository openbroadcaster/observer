<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

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
