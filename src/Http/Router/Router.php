<?php

namespace App\Facades\Http\Router;

use App\Facades\Config\Config;
use App\Facades\Csrf\Csrf;
use App\Facades\Dependency\Container;
use App\Facades\Dependency\ContainerBuilder;
use App\Facades\Devtool\DevTool;
use App\Facades\Http\AbstractController;
use App\Facades\Http\AbstractEventProvider;
use App\Facades\Http\Request;
use App\Facades\Http\Response;
use App\Facades\Http\View;
use App\Facades\Security\Sanitizer;
use ReflectionClass;
use ReflectionMethod;

final class Router extends Route
{
    private static array $params = [];

    private static ?string $url = null;

    public Request $request;

    private Csrf $csrf;
    
    private ContainerBuilder $builder;
    
    private Sanitizer $sanitizer;

    private static ?Collection $route = null;
    
    const TEST_METHOD_PREFIX = 'test';

    public function __construct()
    {
        $this->request = new Request();

        if ($this->request->isOptionsCall()) {
            echo (new Response())->send()->getResponse();
            die;
        }

	    $this->builder = new ContainerBuilder(new Container());
	    $this->csrf    = new Csrf();
	    $this->builder->container->add(Request::class, $this->request);
    }

    public function boot(): void
    {
        $this->parseUrl();
        $this->setParams();
        $this->request->sanitize();
	
	    if ($this->request->getMethod() === Request::METHOD_POST
		    && ! Config::get('app.enable_api')
		    && Config::get('app.csrf')
	    ) {
		    if (! $this->csrf->valid($this->request)) {
			    self::abort(403);
		    }
	    }
        
        $this->runMiddlewares('before');
	    $this->dispatchEvents('before');
    }
    
    public function resolveRequest(): void
    {
	    $this->create(self::$route->getNamespace().'\\'.self::getClass().'Controller');
	
	    if (in_array(http_response_code(), [200,201], true)) {
		    $this->runMiddlewares('after');
		    $this->dispatchEvents('after');
	    }
    }

    private function dispatchEvents(string $when): void
    {
	    $requestEvents = AbstractEventProvider::getRequestListener($when.'Request');
	
	    foreach ($requestEvents as $event) {
		    $reflector = new ReflectionClass($event);
		    (call_user_func_array([$reflector, 'newInstance'], $this->builder->getConstructorParameters($reflector)))
			    ->handle();
	    }
	    
        $events = AbstractEventProvider::getListener(
        	$when,
            self::$route->getNamespace().'\\'.self::getClass().'Controller'
        );

        foreach ($events[self::getAction()] as $event) {
	        $reflector = new ReflectionClass($event);
	        (call_user_func_array([$reflector, 'newInstance'], $this->builder->getConstructorParameters($reflector)))
		        ->handle();
        }
    }

    private function runMiddlewares(string $when): void
    {
    	$path = Config::get('app.middleware_path');
	
	    foreach (self::$route->getMiddleware() as $middleware) {
		    $middleware = $path.ucfirst($middleware);
		    $this->executeMiddleware($middleware, $when);
	    }
	    
	    foreach (Config::get('middleware.'.$when) as $middleware) {
		    $this->executeMiddleware($middleware, $when);
	    }
    }
    
    private function executeMiddleware(string $middleware, string $when): void
    {
	    if (method_exists($middleware, $when)) {
		    $reflector = new ReflectionClass($middleware);
		    (call_user_func_array([$reflector, 'newInstance'], $this->builder->getConstructorParameters($reflector)))
			    ->$when($this->request, $this);
	    }
    }

    public function getCurrentRoute(): Collection
    {
    	if (! self::$route instanceof Collection) {
    		throw new \LogicException('self::$route must be a instance of '.Collection::class);
	    }
    	
        return self::$route;
    }

    public function routeParams(): array
    {
        return [
            'controller'  => self::getClass(),
            'action'      => self::getAction(),
            'namespace'   => self::$route->getNamespace(),
            'rights'      => self::$route->getRights(),
            'middlewares' => self::$route->getMiddleware(),
            'method'      => self::$route->getMethod(),
            'params'      => $this->request->all(),
	        'name'        => self::$route->getName()
        ];
    }

    private function setCurrentRoute(Collection $route): void
    {
        self::$route = $route;
    }

    public static function getClass(): string
    {
        return self::$route->getController();
    }

    public static function getAction(): string
    {
        return self::$route->getAction();
    }

    public static function getNamespace(): ?string
    {
	    return self::$route instanceof Collection ? self::$route->getNamespace() : null;
    }

    public static function getAlias(): string
    {
    	$ns = explode('\\', self::getNamespace());
        return mb_strtolower(end($ns)) === 'http' ? 'http' : 'admin';
    }

    private function create(string $controller)
    {
        if (! class_exists($controller) || ! method_exists($controller, self::getAction())) {
	        throw new \ReflectionException('Controller or method not exist: '. $controller.' method: '.self::getAction());
        }
	
	    $reflectionClass = new ReflectionClass($controller);
	
	    if ($reflectionClass->getMethod(self::getAction())->class !== $controller) {
		    throw new \ReflectionException('Controller not exist : '. $controller);
	    }

        if (str_starts_with(self::getAction(), self::TEST_METHOD_PREFIX) && ! Config::get('app.dev')) {
            throw new \LogicException('Cannot read test method if env is set to production');
        }

        $reflectionMethod = new ReflectionMethod($controller, self::getAction());

        if ($reflectionMethod->isProtected() || $reflectionMethod->isPrivate()) {
	        throw new \LogicException('Cannot make private or protected methods in controller');
        }

        if ($reflectionMethod->getReturnType() === null) {
            throw new \LogicException('Method must have a return type declaration');
        }
        
        if ($reflectionMethod->getReturnType()->getName() !== Response::class) {
            throw new \LogicException('Controller return type declaration must be a instance of '.Response::class);
        }

        AbstractController::$routeParams = $this->routeParams();

        $constructorParams = $this->builder->getConstructorParameters($reflectionClass);
        $params            = $reflectionMethod->getParameters();
        $controller        = call_user_func_array([$reflectionClass, 'newInstance'], $constructorParams);
        $methodParams      = $this->getMethodParams($params, $controller);
        
        if ($reflectionMethod->getNumberOfRequiredParameters() > count($methodParams)) {
	        throw new \BadMethodCallException('Not enough params');
        }

        unset($reflectionMethod, $reflectionClass, $params, $constructorParams);

        if (Config::get('app.dev')) {
	        View::set(['devTool' => DevTool::boot()]);
        }

        $response = call_user_func_array(
	        [$controller, self::getAction()],
	        $methodParams
        );

        echo $response->getResponse();

        ob_end_flush();
        ob_end_clean();
    }

    private function getMethodParams(array $reflectionParams, object $controller): array
    {
	     $combinedParams   = [];
	     $requestParams    = self::$params;
	     $reqParamIterator = 0;
	     $this->sanitizer  = new Sanitizer();

	     foreach ($reflectionParams as $key => $param) {
		     $refParam = new \ReflectionParameter([$controller, self::getAction()], $key);
		     $class    = $refParam->getType()->getName();
		
		     if (class_exists($class) || interface_exists($class)) {
			     $reflector = $this->builder->checkIsInterface(new ReflectionClass($class));
			
			     if ($reflector->hasMethod('__construct')) {
				     $params = $this->builder->reflectConstructorParams($reflector->getConstructor()->getParameters());
				     $this->builder->container->add($class, call_user_func_array([$reflector, 'newInstance'], $params ?? []));
			     } else {
				     $this->builder->container->add($class, new ($reflector->getName())());
			     }
			
			     $combinedParams[$key] = $this->builder->container->get($class);
			     unset($reflector, $refParam, $reflectionParams[$key]);
		     } else {
			     $requestParams[$reqParamIterator] = $this->sanitizer->clear($requestParams[$reqParamIterator]);

			     if ($refParam->isOptional() && ! isset($requestParams[$reqParamIterator])) {
				     unset($refParam, $reflector);
				     $reqParamIterator++;
				     continue;
			     }
			
			     $type = preg_replace(
				     '/.*?(\w+)\s+\$' . $refParam->name . '.*/',
				     '\\1',
				     $refParam->__toString()
			     );
			
			     if ($type === 'int') {
				     $type = 'integer';
			     }
			
			     if ($type === 'float') {
				     $type = 'double';
			     }
			
			     if (gettype($requestParams[$reqParamIterator]) !== $type) {
				     self::abort(400, 'Wrong param type, param: ' . $requestParams[$reqParamIterator]);
			     }
			
			     $combinedParams[$key] = $requestParams[$reqParamIterator];
			     $reqParamIterator++;
			     unset($refParam, $reflector);
		     }
	     }
	
	     return $combinedParams;
    }

    public function setParams(): void
    {
	    if (php_sapi_name() === 'cli' || php_sapi_name() === 'cli-server') {
		    $this->resolveCliCall();
	    }
	    
        $routeExist = false;

        foreach (self::$routes as $key => $route) {
	        $pattern = preg_replace('/{(.*?)}/', '(.*?)', $key, -1);

            if (preg_match('#^'.$pattern.'$#', self::$url, $matches)) {
                if ($this->request->getMethod() !== (string) $route->getMethod()) {
                    self::abort(405);
                }

                $routeExist = true;
                $this->setCurrentRoute($route);
                $this->setMatches(array_slice($matches, 1), ltrim(str_replace('(.*?)', null, $pattern), '/'));
                break;
            }
        }
		
        if (! $routeExist) {
            self::abort();
        }
    }
	
	private function setMatches(array $matches, string $matchesSeparator = '/'): void
	{
		if ($matchesSeparator === '') {
			$matchesSeparator = '/';
		}
		
		if (str_contains($matches[0][0], $matchesSeparator)) {
			$matches = explode($matchesSeparator, $matches[0][0]);
		}
		
		foreach ($matches as $value) {
			self::$params[] = $value;
		}
	}

    public static function url(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    private function parseUrl(): void
    {
        if ((string) Config::get('app.url') !== '/') {
            self::$url = str_replace(Config::get('app.url'), '', self::url());
        } else {
            self::$url = self::url();
        }

        $this->setQueryStringParams();

        self::$url = preg_replace('/\?.*/',
            '',
            filter_var(self::$url, FILTER_SANITIZE_URL)
        );

        if ((string) self::$url !== '/') {
            self::$url = rtrim(self::$url, '/');
        }
    }

    private function setQueryStringParams(): void
    {
        parse_str(parse_url(self::$url)['query'], $str);

        if (! empty($str)) {
            foreach ($str as $key => $item) {
                self::$params[$key] = $item;
            }
        }
    }

    public static function abort(int $code = 404, ?string $message = null): void
    {
        if (Config::get('app.enable_api') || Request::isAjax()) {
	        echo (new Response())->json()->setData(['msg' => Response::RESPONSE_CODES[$code]])->setCode($code)->getResponse();
            exit;
        } else {
            exit(View::display('/errors/error.twig', ['exception' => ['getCode' => $code, 'getMessage' => $message]]));
        }
    }

    public static function csrfPath(): string
    {
        return self::$route->getController().'@'.self::$route->getAction();
    }
	
	private function resolveCliCall()
	{
		$response = new Response();
		
		if (preg_match('/\.(?:css|js|jpe?g|gif|png|ico|ttf|woff)$/', self::$url)) {
			$path = app_path('/public'.self::$url);
			$file = file_get_contents($path);
			
			if (str_contains(self::$url, '.css')) {
				$response->setHeader('Content-type', 'text/css');
			} else if (str_contains(self::$url, '.js')) {
				$response->setHeader('Content-type', 'application/javascript');
			} else if (str_contains(self::$url, '.ttf') || str_contains(self::$url, '.woff')) {
				$response->setHeader('Content-type', 'font/woff2');
			} else {
				$response->setHeader('Content-type', mime_content_type($path));
			}
			
			echo $response->setContent($file)->getResponse();
			exit;
		}
	}
}
