<?php

namespace OB\Classes\Metadata;

class Coordinates extends \OB\Classes\Base\Metadata
{
    public function processRow(&$row)
    {
        $coordinates = $row['metadata_' . $this->name];
        if ($coordinates) {
            // unpack binary if needed. note this check is very specific to this application and not a general solution
            // the expected binary data is 25 bytes long, and the expected non-binary data is less than that
            // note there is a very unlikely edge case if someone sets the default value to be a string of 25 characters (which is not possible via the UI, but could be done via the API)
            // TODO this should be fixed with better metadata abstraction
            if (strlen($coordinates) == 25) {
                $data = unpack('x/x/x/x/corder/Ltype/dlat/dlon', $coordinates);
                $coordinates = $data['lat'] . ',' . $data['lon'];
            }

            $coordinates = explode(',', $coordinates);
            if (count($coordinates) == 2) {
                $latitude = (float) $coordinates[0];
                $longitude = (float) $coordinates[1];
                $row['metadata_' . $this->name] = [$latitude, $longitude];

                // set, early return.
                return;
            }
        }

        // not set or not valid
        $row['metadata_' . $this->name] = null;
    }
}
