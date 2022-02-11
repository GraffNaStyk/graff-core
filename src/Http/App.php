<?php

namespace App\Facades\Http;

use App\Facades\Helpers\Dir;
use App\Facades\Http\Router\Router;

final class App
{
	public Router $router;
	const PER_PAGE = 25;
	
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
	}
}
