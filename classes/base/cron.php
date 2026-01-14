<?php

namespace OpenBroadcaster\Base;

abstract class Cron
{
    abstract public function interval(): int;
    abstract public function run(): bool;
}
