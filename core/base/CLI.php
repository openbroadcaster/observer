<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OpenBroadcaster\Base;

/**
 * Abstract class extended by individual CLI commands.
 *
 * @package Class
 */
abstract class CLI
{
    protected $db;
    protected $models;

    protected array $help;

    final public function __construct()
    {
        $this->db = \OBFDB::get_instance();
        $this->models = \OBFModels::get_instance();

        if (! isset($this->help)) {
            $this->help = [];
        }
    }

    final public function help(): array
    {
        return $this->help;
    }

    abstract public function run(array $args): bool;
}