<?php

namespace App\Facades\Helpers;

class Str
{
	public static function hash(int $length): string
	{
		return bin2hex(random_bytes($length));
	}

    public static function getUniqueStr(string $model, string $column = 'hash', int $length = 50): string
    {
        do {
            $hash  = self::hash($length);
            $check = $model::select([$column])->where($column, '=', $hash)->first();
        } while (! empty($check));

        return $hash;
    }
	
	public static function sanitize(string $string): string
	{
		$from   = ['ą','ć','ę','ł','ń','ó','ś','ż','ź'];
		$to     = ['a','c','e','l','n','o','s','z','z'];
		
		$string = str_replace($from, $to, mb_strtolower($string));
		$string = preg_replace('~[^\\pL\d]+~u', '-', $string);
		$string = preg_replace('~[^-\w]+~', '', $string);
		
		return trim($string);
	}
	
	public static function toSnakeCase(string $string): string
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]+|(?<!^|\d)[\d]+/', '_$0', $string));
	}
	
	public static function toLineSeparator(string $string): string
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]+|(?<!^|\d)[\d]+/', '-$0', $string));
	}
	
	public static function toSeoUrl(string $string): string
	{
		return trim(strtolower(preg_replace('/[\s]+/', '-', $string)));
	}
}
