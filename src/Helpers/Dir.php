<?php

namespace App\Facades\Helpers;


class Dir
{
    public static function create(string $path, int $permission = 0775): void
    {
    	$path = rtrim($path, '/');
	
	    if (! is_dir($path)) {
		    $mask = umask(0);
		
		    if (! mkdir($path, $permission, true)) {
			    umask($mask);
			    throw new \DomainException('Cannot create directory in path '.$path);
		    }
		
		    umask($mask);
	    }
    }
}
