<?php

namespace OB\Classes\Metadata;

class Boolean extends \OB\Classes\Base\Metadata
{
    public function processRow(&$row)
    {
        $row['metadata_' . $this->name] = (bool) $row['metadata_' . $this->name];
    }
}
