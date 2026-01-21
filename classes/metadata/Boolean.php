<?php

namespace OpenBroadcaster\Metadata;

class Boolean extends \OpenBroadcaster\Base\Metadata
{
    public function processRow(&$row)
    {
        // TODO should this ever not be set?
        if (isset($row['metadata_' . $this->name])) {
            $row['metadata_' . $this->name] = (bool) $row['metadata_' . $this->name];
        }
    }
}
