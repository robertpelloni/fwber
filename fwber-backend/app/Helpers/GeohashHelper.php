<?php

namespace App\Helpers;

class GeohashHelper
{
    private static $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';
    private static $bits = [16, 8, 4, 2, 1];

    public static function encode($latitude, $longitude, $precision = 12)
    {
        $is_even = true;
        $lat = [-90.0, 90.0];
        $lon = [-180.0, 180.0];
        $bit = 0;
        $ch = 0;
        $geohash = '';

        while (strlen($geohash) < $precision) {
            if ($is_even) {
                $mid = ($lon[0] + $lon[1]) / 2;
                if ($longitude > $mid) {
                    $ch |= self::$bits[$bit];
                    $lon[0] = $mid;
                } else {
                    $lon[1] = $mid;
                }
            } else {
                $mid = ($lat[0] + $lat[1]) / 2;
                if ($latitude > $mid) {
                    $ch |= self::$bits[$bit];
                    $lat[0] = $mid;
                } else {
                    $lat[1] = $mid;
                }
            }

            $is_even = !$is_even;
            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= self::$base32[$ch];
                $bit = 0;
                $ch = 0;
            }
        }

        return $geohash;
    }
}
