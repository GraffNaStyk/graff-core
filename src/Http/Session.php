<?php

namespace App\Facades\Http;

use App\Facades\Http\Router\Router;
use App\Facades\Property\Get;
use App\Facades\Property\Has;
use App\Facades\Property\Remove;
use App\Facades\Property\Set;
use App\Facades\Validator\Type;

class Session
{
    public static function set($item, $data): void
    {
        $_SESSION = array_merge($_SESSION, Set::set($_SESSION, Type::get($data), $item));
    }

    public static function get($item)
    {
        return Get::check($_SESSION, $item);
    }

    public static function all(): array
    {
        return $_SESSION;
    }

    public static function has($item): bool
    {
        return Has::check($_SESSION, $item);
    }

    public static function remove($item): void
    {
        $_SESSION = Remove::remove($_SESSION, $item);
    }

    public static function checkIfDataHasBeenProvided($request)
    {
        $_SESSION['unused'] = $request;
    }

    public static function destroy(): void
    {
        session_destroy();
    }
    
    public static function id(): string|bool
    {
    	return session_id();
    }
}
