<?php

namespace App\Facades\Dependency;

use App\Facades\Config\Config;
use ReflectionClass;

class ContainerBuilder
{
	private static ?ContainerBuilder $containerBuilder = null;
	
	public function __construct(public Container $container)
	{
		if (self::$containerBuilder instanceof ContainerBuilder || self::$containerBuilder !== null) {
			throw new \LogicException('Cannot load container two times');
		}
		
		if (self::$containerBuilder === null) {
			self::$containerBuilder = $this;
		}
	}
	
	public function reflectConstructorParams(array $reflectionParams): array
	{
		$combinedParams = [];
		
		foreach ($reflectionParams as $key => $refParam) {
			$class = $refParam->getClass()?->getName();
			
			if (class_exists($class) || interface_exists($class)) {
				$reflector = $this->checkIsInterface(new ReflectionClass($class));
				
				if (! class_exists($reflector->getName())) {
					throw new \LogicException('Class : ' . $class . ' not exist');
				}
				
				if (! $this->container->has($class)) {
					if ($reflector->hasMethod('__construct')) {
						$params = $this->reflectConstructorParams($reflector->getConstructor()->getParameters());
						$this->container->add($class, call_user_func_array([$reflector, 'newInstance'], $params ?? []));
					} else {
						$this->container->add($class, new ($reflector->getName())());
					}
				}
				
				$combinedParams[$key] = $this->container->get($class);
				unset($reflector);
			}
		}
		
		return $combinedParams;
	}
	
	public function checkIsInterface(object $reflector): object
	{
		if ($reflector->isInterface() && Config::has('interfaces.' . $reflector->getName())
			&& interface_exists($reflector->getName())
		) {
			$reflector = new ReflectionClass(Config::get('interfaces.' . $reflector->getName()));
		} else {
			if ($reflector->isInterface()) {
				throw new \LogicException($reflector->getName() . ' is not register in interfaces.php or not exist');
			}
		}
		
		return $reflector;
	}
	
	public function getConstructorParameters(ReflectionClass $reflector): array
	{
		$constructorParams = [];
		
		if ($reflector->hasMethod('__construct')) {
			$constructorParams = $this->reflectConstructorParams(
				$reflector->getConstructor()->getParameters()
			);
		}
		
		return $constructorParams;
	}
	
	public static function getInstance(): ContainerBuilder
	{
		if (self::$containerBuilder == null) {
			throw new \LogicException('Cannot get Container instance before create them');
		}
		
		return self::$containerBuilder;
	}
}
