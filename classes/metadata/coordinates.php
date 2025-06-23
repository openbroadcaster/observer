<?php

namespace OB\Classes\Metadata;

class Coordinates extends \OB\Classes\Base\Metadata
{
    private function isPointData($data)
    {
        // POINT binary should be exactly 25 bytes long
        if (strlen($data) !== 25) {
            return false;
        }

        // Check for the SRID and WKB type for POINT
        $header = unpack('Lsrid/cbyteOrder/Lwkbtype', $data);

        // WKB type for POINT is 1
        return $header['wkbtype'] === 1;
    }

    public function processRow(&$row)
    {
        $coordinates = $row['metadata_' . $this->name];
        if ($coordinates) {
            if ($this->isPointData($coordinates)) {
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
