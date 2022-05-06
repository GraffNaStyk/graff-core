<?php

namespace App\Facades\Http;

use App\Facades\Http\Router\Router;
use App\Facades\Property\Get;
use App\Facades\Property\Has;

class Cookie
{
	public static function flash($item, $value = 1, $seconds = 60): void
	{
		setcookie($item, $value, time() + $seconds, '/', $_SERVER['SERVER_NAME'], Router::checkProtocol() === 'https', true);
	}
	
	public static function get($item): mixed
	{
		return Get::check($_COOKIE, $item);
	}
	
	public static function has($item): bool
	{
		return Has::check($_COOKIE, $item);
	}
	
	public static function all(): array
	{
		return $_COOKIE;
	}
	
	public static function remove($item): void
	{
		unset($_COOKIE[$item]);
		setcookie($item, false, - 1, '/', $_SERVER['SERVER_NAME'], Router::checkProtocol() === 'https', true);
	}
}
