<?php

namespace App\Facades\Autoload;

class Autoload
{
    public static function run(string $class): void
    {
        $classArr  = explode('\\', $class);
        $className = end($classArr);
        array_pop($classArr);
        $className = strtolower(implode('/', $classArr)).'/'.$className.'.php';

        if (is_readable(path($className))) {
            require_once path($className);
        }
    }
}
