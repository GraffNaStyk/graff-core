<?php

namespace App\Facades\Http;

use App\Facades\Config\Config;

abstract class AbstractEventProvider
{
    public static function getListener(string $when, string $listener): ?array
    {
    	if (Config::has('events.'.$when.'.'.$listener)) {
		    return Config::get('events.'.$when.'.'.$listener);
	    }

    	return [];
    }
	
	public static function getRequestListener(string $when): ?array
	{
		if (Config::has('events.'.$when)) {
			return Config::get('events.'.$when);
		}
		
		return [];
	}
}
