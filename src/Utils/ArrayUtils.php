<?php

namespace App\Utils;

class ArrayUtils
{
    public static function flatten(array $array): array
    {
        $flattened = [];

        foreach(array_keys($array) as $k) {
            $value = $array[$k];
            if (is_scalar($value)) {
                $flattened[] = $value;
            } elseif (is_array($value)) {
                $flattened = array_merge($flattened,
                    self::flatten($value)
                );
            }
        }

        return $flattened;
    }
}
