<?php

namespace App\Facades\Http\Router;

final class Collection
{
	private string $controller;
	private string $action;
	private string $namespace;
	private string $method;
	private int $rights;
	private array $middlewares = [];
	private string $alias = 'http';
	private string $name;
	
	public function __construct
	(
		string $controller,
		string $action,
		string $namespace,
		string $method,
		int $rights,
		string $name,
		?string $middleware,
		?string $alias
	)
	{
		$this->controller = $controller;
		$this->action     = $action;
		$this->namespace  = $namespace;
		$this->method     = $method;
		$this->rights     = $rights;
		$this->name       = $name;
		
		if ($alias !== null) {
			$this->alias = trim(ltrim($alias, '/'));
		}
		
		if ($middleware) {
			$this->middlewares[] = $middleware;
		}
	}
	
	public function getController(): string
	{
		return $this->controller;
	}
	
	public function getAction(): string
	{
		return $this->action;
	}
	
	public function getNamespace(): string
	{
		return $this->namespace;
	}
	
	public function getMethod(): string
	{
		return $this->method;
	}
	
	public function getRights(): int
	{
		return $this->rights;
	}
	
	public function getMiddleware(): array
	{
		return $this->middlewares;
	}
	
	public function middleware(array $middlewares): void
	{
		$this->middlewares = [...$this->middlewares, ...$middlewares];
	}
	
	public function getAlias(): string
	{
		return $this->alias;
	}
	
	public function getName(): string
	{
		return $this->name;
	}
	
	public function setName(string $name): void
	{
		$this->name = $name;
	}
}
