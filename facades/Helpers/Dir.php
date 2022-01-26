<?php

namespace App\Facades\Helpers;


class Dir
{
    public static function create(string $path, int $permission = 0775): void
    {
    	$path = rtrim($path, '/');

        if (! is_dir($path)) {
            mkdir($path, $permission, true);
        }
    }
}