<?php

namespace App\Facades\Http;

use App\Facades\Http\Router\Router;
use App\Facades\Storage\Storage;
use App\Facades\Url\Url;
use App\Facades\Validator\Validator;

abstract class AbstractController
{
	public static array $routeParams = [];
	public static array $routes      = [];
	
    public function __construct()
    {
        $this->boot();
    }

    public function boot(): void
    {
        Storage::private()->make('logs')->make('cache');
        Session::remove('beAjax');
        Session::clearMsg();
    }

    public function redirect(?string $path, int $code = 302, bool $direct = false): Response
    {
        return (new Response())->redirect($path, $code, $direct)->send();
    }
    
    public function redirectToRoute(string $route, int $code = 302, bool $direct = false): Response
    {
    	$route = $this->routes($route);
    	
    	if ($route === null) {
    		throw new \LogicException('Route '.$route.' not exist');
	    }

	    return (new Response())->redirect($route, $code, $direct)->send();
    }

    public function path(string $route, bool $relative = false): string
    {
	    $route = $this->routes($route);
	
	    if ($route === null) {
		    throw new \LogicException('Route '.$route.' not exist');
	    }
	    
	    if ($relative) {
	    	return $route;
	    }
	    
	    return Url::fullWithAlias().$route;
    }
    
    public function setData(array $data): void
    {
        View::set($data);
    }

    public function render(array $data = []): Response
    {
    	return (new Response())->setContent(View::render($data))->send();
    }
	
	public function validate(array $request, object $rule): bool
	{
		return Validator::validate($request, $rule->getRules());
	}

    public function sendSuccess(string $message = null, array $params = [], int $status = 200): Response
    {
        Session::set('beAjax', true);
        return (new Response())
	        ->json()
	        ->setCode($status)
	        ->setData([
		        'ok'     => true,
		        'msg'    => $message,
		        'params' => $params,
	        ])
	        ->send();
    }

    public function sendError(string $message = null, array $params = [], int $status = 400): Response
    {
	    Session::set('beAjax', true);
	    return (new Response())
		    ->json()
		    ->setCode($status)
		    ->setData([
			    'ok'     => false,
			    'msg'    => $message,
			    'inputs' => Validator::getErrors(),
			    'csrf'   => Session::get('@csrf.'.Router::csrfPath()),
			    'params' => $params,
		    ])
		    ->send();
    }

    public function response(array $data = [], int $status = 200, array $headers = []): Response
    {
    	$response = new Response();
    	$response->setData($data)->setCode($status);
    	
    	if (! empty($headers)) {
    		$response->setHeaders($headers);
	    }
    	
        return $response->send();
    }
    
    public function routeParams(?string $param = null)
    {
    	if ($param === null) {
    		return static::$routeParams;
	    }
    	
    	return static::$routeParams[$param];
    }
    
    private function routes(string $route): ?string
    {
        return self::$routes[$route] ?? null;
    }
}
