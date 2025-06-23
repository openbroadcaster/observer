<?php

namespace OB\Classes\Metadata;

class Boolean extends \OB\Classes\Base\Metadata
{
    public function processRow(&$row)
    {
        // TODO should this ever not be set?
        if (isset($row['metadata_' . $this->name])) {
            $row['metadata_' . $this->name] = (bool) $row['metadata_' . $this->name];
        }
    }
}
