<?php

namespace App\Facades\Http;

use App\Facades\Config\Config;
use App\Facades\Helpers\Dir;
use App\Facades\Http\Router\Router;

final class App
{
	public Router $router;
	
	public function __construct(Router $router)
	{
		$this->router = $router;
	}
	
	public function run(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		
		Dir::create(storage_path('/var/logs'));
		Dir::create(storage_path('/var/cache'));
		Dir::create(storage_path('/var/db'));
		
		if (php_sapi_name() !== 'cli') {
			if (Config::get('app.enable_api')) {
				require_once app_path('/app/routes/api.php');
			} else if (! Config::get('app.dev')) {
				if (is_readable(storage_path('/var/cache/routes.cache'))) {
					$this->router->setRoutes(unserialize(file_get_contents(storage_path('/var/cache/routes.cache'))));
					$this->router->setUrls(unserialize(file_get_contents(storage_path('/var/cache/urls.cache'))));
				} else {
					$this->setRouteCache();
				}
			} else {
				unlink(storage_path('/var/cache/routes.cache'));
				unlink(storage_path('/var/cache/urls.cache'));
				require_once app_path('/app/routes/http.php');
			}
			
			$this->router->boot();
			$this->router->resolveRequest();
		}
	}
	
	private function setRouteCache(): void
	{
		require_once app_path('/app/routes/http.php');
		$routes = $this->router->getRoutes();
		$urls   = [];
		
		foreach ($routes as $key => $route) {
			$urls[$route->getName()] = $key;
		}

		file_put_contents(storage_path('/var/cache/urls.cache'), serialize($urls));
		file_put_contents(storage_path('/var/cache/routes.cache'), serialize($routes));
	}
}
