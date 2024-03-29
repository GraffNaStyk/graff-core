<?php

namespace App\Facades\Http\Router;

use App\Facades\Config\Config;
use App\Facades\Csrf\Csrf;

abstract class Route
{
    protected static array $routes;
    protected static string $namespace;
    protected static ?string $middleware = null;
    protected static ?string $alias = null;
    protected static array $urls = [];

    public static function namespace(string $namespace, callable $function): void
    {
        static::$namespace = $namespace;
        $function();
        static::$middleware = null;
    }

    public static function middleware(string $middleware, callable $function): void
    {
        static::$middleware = ucfirst($middleware);
        $function();
        static::$middleware = null;
    }

    public static function get(string $url, string $controller, int $rights = 4): Collection
    {
        return self::match($url, $controller, 'get', $rights);
    }

    public static function post(string $url, string $controller, int $rights = 4): Collection
    {
        return self::match($url, $controller, 'post', $rights);
    }

    public static function put(string $url, string $controller, int $rights = 4): Collection
    {
        return self::match($url, $controller, 'put', $rights);
    }

    public static function group(
        array $urls,
        string $controller,
        string $method = 'post',
        int $rights = 4,
        array $middlewares = []
    ): void
    {
        foreach ($urls as $url) {
            $collection = self::match($url, $controller, $method, $rights);
            $collection->middleware($middlewares);
        }
    }

    public static function delete(string $url, string $controller, int $rights = 4): Collection
    {
        return self::match($url, $controller, 'delete', $rights);
    }

    public static function alias(string $alias, callable $function): void
    {
        self::$alias = $alias;
        $function();
        self::$alias = null;
    }

    public static function crud(string $url, string $controller, int $rights = 4): void
    {
        self::get($url, $controller.'@index', $rights);
        self::get($url.'/add', $controller.'@add', $rights);
        self::get($url.'/edit/{id}', $controller.'@edit', $rights);
        self::get($url.'/show/{id}', $controller.'@show', $rights);
        self::post($url.'/store', $controller.'@store', $rights);
        self::post($url.'/update', $controller.'@update', $rights);
        self::post($url.'/delete', $controller.'@delete', $rights);
    }

    private static function match(string $as, string $route, string $method, int $rights): Collection
    {
	    $routes = explode('@', $route);
	
	    $collection = new Collection(
		    ucfirst($routes[0]),
		    $routes[1] ?? 'index',
		    self::$namespace,
		    $method,
		    $rights,
		    $route,
		    self::$middleware,
		    self::$alias
	    );

        if (self::$alias === null) {
            $url = self::$alias.$as ?? $routes[0].'/'.$routes[1];
        } else {
            $url = self::$alias.rtrim($as, '/') ?? $routes[0].'/'.$routes[1];
        }
	
	    self::$urls[$route] = $url;
	    self::$routes[$url] = $collection;

        if ($method !== 'get' && ! Config::get('app.enable_api')) {
            Csrf::make($route);
        }

        return $collection;
    }

    public static function when(string $when, string $then): void
    {
	    $route = rtrim(Router::url(), '/');

        if ($route === rtrim($when, '/')) {
            static::redirect($then);
        }
    }
	
	public static function redirect(string $path, int $code = 302): void
	{
		session_write_close();
		session_regenerate_id();
		
		header(
			'location: '.$path,
			true,
			$code
		);
		
		exit;
	}

    public static function goTo(string $url): void
    {
        header('location: '.$url, true, 301);
        exit;
    }

    public static function checkProtocol(): string
    {
	    return isset($_SERVER['HTTPS']) || isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443 ? 'https' : 'http';
    }
	
	public static function urls(): array
	{
		return self::$urls;
	}
	
	public function getRoutes(): array
	{
		return self::$routes;
	}
	
	public function setRoutes(array $routes): void
	{
		self::$routes = $routes;
	}
	
	public function setUrls(array $urls): void
	{
		self::$urls = $urls;
	}
}
