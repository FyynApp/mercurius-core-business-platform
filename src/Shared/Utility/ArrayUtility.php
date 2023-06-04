<?php

namespace App\Shared\Utility;


class ArrayUtility
{
    public static function allValuesAreClass(array $array, string $enumClass): bool
    {
        foreach ($array as $value) {
            if (!is_a($value, $enumClass)) {
                return false;
            }
        }
        return true;
    }
}
