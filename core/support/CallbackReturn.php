<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * CallbackReturn instances are created when firing callbacks. It contains two
 * public variables, $r and $v. $r is the first argument passed when creating a
 * new instance, and $v is set to TRUE if more arguments have been passed to it,
 * FALSE otherwise.
 *
 * @package Support
 */
namespace OpenBroadcaster\Support;

class CallbackReturn
{
    public $r;
    public $v;

    public function __construct()
    {
        $args = func_get_args();

        if (isset($args[0])) {
            $this->v = $args[0];
        } else {
            $this->v = null;
        }

        if (isset($args[1]) && !empty($args[1])) {
            $this->r = true;
        } else {
            $this->r = false;
        }
    }
}
