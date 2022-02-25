<?php

namespace App\Facades\Url;

use App\Facades\Http\Router\Router;

class Url
{
    public static function segment($string, $offset, $delimiter = '/'): ?string
    {
        $string = explode($delimiter, $string);

        if ($offset === 'end' || $offset === 'last') {
            return end($string);
        }
        
        return $string[$offset] ?? null;
    }

    public static function isLocalhost(): bool
    {
        return in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
    }
	
	public static function fullWithAlias(): string
	{
		$url = Router::checkProtocol().'://'.$_SERVER['HTTP_HOST'];
		
		if (self::withAlias() === null) {
			return $url;
		}
		
		return $url.'/'.self::withAlias();
	}
	
	public static function full(): string
	{
		return Router::checkProtocol().'://'.$_SERVER['HTTP_HOST'];
	}
    
    private static function withAlias(): ?string
    {
    	return Router::getAlias() !== 'http' ? Router::getAlias() : null;
    }
}
