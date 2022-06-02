<?php

namespace App\Facades\Error;

use App\Facades\Config\Config;
use App\Facades\Http\Router\Router;
use App\Facades\Http\View;
use App\Facades\Log\Log;

class ErrorListener
{
	private static Router $router;

	public static function setRouter(Router $router): void
	{
		self::$router = $router;
	}

	public static function listen(): void
	{
		$error = error_get_last();

		if (! empty($error)
			&& in_array($error['type'], [E_USER_ERROR, E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])
		) {
			throw new \Exception($error);
		}
	}

	public static function exceptionHandler(\Throwable $exception)
	{
		if (Config::get('app.error_listener')) {
			if (! class_exists(Config::get('app.error_listener'))) {
				exit('Your custom exception listener " '.Config::get('app.error_listener').'" not exist!');
			}

			(new (Config::get('app.error_listener')))->listen($exception, self::$router);
		} else if (php_sapi_name() === 'cli') {
			dd($exception);
			exit;
		} else if (Config::get('app.dev')) {
			(new LogErrorFormatter($exception, self::$router))->format();
		} else {
			if (! in_array($exception::class, (array) Config::get('app.no_logged_exceptions'), true)) {
				Log::custom('php', [
						'line'    => $exception->getLine(),
						'file'    => $exception->getFile(),
						'trace'   => $exception->getTraceAsString(),
						'code'    => $exception->getCode(),
						'message' => $exception->getMessage(),
						'router'  => self::$router->routeParams()
					]
				);
			}

            exit(View::display('errors/error.php', ['exception' => $exception]));
		}
	}

	public static function setDisplayErrors(): void
	{
		if (Config::get('app.dev')) {
			ini_set('display_startup_errors', 1);
			error_reporting(Config::get('app.reporting_levels'));
		} else {
			ini_set('display_startup_errors', 0);
			error_reporting(0);
		}
	}
}