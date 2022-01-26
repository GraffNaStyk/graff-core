<?php

namespace App\Facades\Devtool;

class DevTool
{
	public static function boot(): string
	{
		$time = round(microtime(true) - APP_START, 4);
		require_once __DIR__.'/index.php';

		return ob_get_clean();
	}
}
