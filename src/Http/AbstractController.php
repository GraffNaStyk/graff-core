<?php

namespace App\Facades\Http;

use App\Facades\Http\Router\Router;
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
        Session::remove('beAjax');
    }
    
    public function redirectToRoute(string $route, array $params = [], bool $direct = false): Response
    {
    	$route = $this->routes($route);
    	
    	if ($route === null) {
    		throw new \LogicException('Route '.$route.' not exist');
	    }
	
	    if (! empty($params)) {
		    foreach ($params as $key => $param) {
			    $route = str_replace('{'.$key.'}', $param, $route);
		    }
	    }

	    return (new Response())->redirect($route, 302, $direct)->send();
    }

    public function getRoute(string $route, array $params = [], bool $relative = false): string
    {
	    $route = $this->routes($route);
	
	    if ($route === null) {
		    throw new \LogicException('Route '.$route.' not exist');
	    }
	
	    if (! empty($params)) {
		    foreach ($params as $key => $param) {
			    $route = str_replace('{'.$key.'}', $param, $route);
		    }
	    }
	    
	    if ($relative) {
	    	return $route;
	    }
	    
	    return Url::full().$route;
    }
    
    public function setData(array $data): void
    {
        View::set($data);
    }

    public function render(array $data = [], array $headers = []): Response
    {
    	return (new Response())->setContent(View::render($data))->setHeaders($headers)->send();
    }
	
	public function validate(array $request, object $rule): bool
	{
		$validate = Validator::validate($request, $rule->getRules());
		
		if (method_exists($rule, 'afterValidate')) {
			Validator::setErrors($rule->afterValidate(Validator::getErrors()));
		}
		
		return $validate;
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
	
	public function responseJson(array $data = [], int $status = 200, array $headers = []): Response
	{
		$response = new Response();
		$response->json()->setData($data)->setCode($status);
		
		if (! empty($headers)) {
			$response->setHeaders($headers);
		}
		
		return $response->send();
	}
    
    public function routeParams(?string $param = null): mixed
    {
    	if ($param === null) {
    		return static::$routeParams;
	    }
    	
    	return static::$routeParams[$param] ?? null;
    }
    
    private function routes(string $route): ?string
    {
        return self::$routes[$route] ?? null;
    }
    
    public function getUser(): ?object
    {
		return Session::get('user') ?: null;
    }
}
