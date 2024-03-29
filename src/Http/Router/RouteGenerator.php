<?php

namespace App\Facades\Http\Router;

use App\Facades\Url\Url;

class RouteGenerator
{
	public static function generate(string $route, ?array $params = [], ?array $queryParams = []): string
	{
		$url = Url::full().Route::urls()[$route];
		
		if ($url === null) {
			throw new \LogicException('Route '.$route.' not exist');
		}
		
		if (! empty($params)) {
			foreach ($params as $key => $param) {
				$url = str_replace('{'.$key.'}', $param, $url);
			}
		} else {
			$url = rtrim(preg_replace('/{(.*?)}/', null, $url), '/');
		}

		if (!empty($queryParams)) {
			$url .= '?';
			
			foreach ($queryParams as $key => $param) {
				if ($param) {
					$url .= $key.'='.$param.'&';
				}
			}
		}
		
		return rtrim($url, '&');
	}
}
