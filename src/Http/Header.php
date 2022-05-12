<?php

namespace App\Facades\Http;

use App\Facades\Config\Config;

trait Header
{
    protected function prepareHeaders(): void
    {
        header('X-Frame-Options: sameorigin');
	    header('Cache-Control: no-cache, no-store, must-revalidate');
	    header('Pragma: no-cache');
	    header('Expires: 0');
        header('Referrer-Policy: no-referrer');

        if (Config::get('app.security.enabled')) {
            header('Content-Security-Policy: '.Config::get('app.security.protection'));
        }

        header('Strict-Transport-Security: max-age=31536000');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Permitted-Cross-Domain-Policies: none');
    }

    public static function setAllowedOptions()
    {
        header('Access-Control-Allow-Origin', '*');
        header('Access-Control-Allow-Headers', '*');
        header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }
}
