<?php

namespace App\Facades\Db;

use App\Facades\Config\Config;
use App\Facades\Dependency\AttributeReflector;
use App\Facades\Helpers\Str;

class Entity
{
	private array $reserved   = ['table', 'trigger'];
	private array $properties = [];
	private AttributeReflector $reflector;
	
	public function __construct(private string $model)
	{
		$this->reflector = new AttributeReflector();
	}
	
	public function parse(array|object $items): array|object
	{
		if (! Config::get('app.use_entity')) {
			return $items;
		}

		$reflection = new \ReflectionClass($this->model);
		$result     = null;
		
		$this->prepareProperties($reflection);
		
		if (is_array($items)) {
			foreach ($items as $item) {
				$result[] = $this->makeEntity($item);
			}
		} else {
			$result = $this->makeEntity($items);
		}
		
		return $result;
	}
	
	private function prepareProperties(\ReflectionClass $reflectionClass)
	{
		foreach ($reflectionClass->getProperties() as $property) {
			if (in_array($property->getName(), $this->reserved, true)) {
				continue;
			}

			$this->reflector->reflect($property);
			$setter = 'set'.$property->getName();

			$type = match (true) {
				$property->isPrivate()   => 'private',
				$property->isProtected() => 'protected',
				$property->isPublic()    => 'public',
				default                  => throw new \Exception('Unexpected match value')
			};
			
			if (empty(get_object_vars($this->reflector))) {
				$name = Str::toSnakeCase($property->getName());
			} else {
				$name = $this->reflector->get('name');
			}
			
			$this->properties[$name] = [
				'propertyName' => $property->getName(),
				'setter'       => $reflectionClass->hasMethod($setter) ? $reflectionClass->getMethod($setter)->getName() : null,
				'type'         => $type,
			];
		}
	}
	
	private function makeEntity(object $item): object
	{
		$object = new $this->model;
		
		foreach ($item as $key => $value) {
			if (isset($this->properties[$key])) {
				$current = $this->properties[$key];
				if ($current['type'] !== 'public' && $current['setter'] === null) {
					throw new \Exception(
						'Cannot set value for field '.$current['propertyName']. ' property has '.$current['type'].' access'
					);
				}
				
				if ($current['type'] === 'public') {
					$object->{$current['propertyName']} = $value;
				} else {
					$object->{$current['setter']}($value);
				}
			}
		}
		
		return $object;
	}
}
