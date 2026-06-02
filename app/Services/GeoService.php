<?php

namespace App\Services;

class GeoService
{
    public function bucket(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return sprintf('%+.3f:%+.3f', round($latitude, 3), round($longitude, 3));
    }
}
