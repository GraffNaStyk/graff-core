<?php

namespace App\Facades\Validator;

class Type
{
    public static function get($item): string|array|bool|int|null|float|object
    {
        if (is_array($item) || is_object($item)) {
            return $item;
        }

        if (is_bool($item)) {
            return (bool) $item;
        }

        if ($item === null || (string) trim($item) === '' || $item === 'null') {
            return null;
        } else if (preg_match('/^[+-]?(\d*\.\d+([eE]?[+-]?\d+)?|\d+[eE][+-]?\d+)$/',
	        str_replace([',', ' '], ['.', ''], $item)
        )) {
            return (float) str_replace([',', ' '], ['.', ''], $item);
        } else if (is_numeric($item)) {
            if ((int) $item >= 2147483647) {
	            return 2147483647;
	        }

            return (int) $item;
        }

	    return trim($item);
    }
}
