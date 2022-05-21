<?php

namespace App\Facades\Db;

use App\Attributes\Table\Column;
use App\Facades\Config\Config;
use App\Facades\Dependency\AttributeReflector;

class EntityConverter
{
	public function __construct(private string $model)
	{
		$this->reflector = new AttributeReflector();
	}
	
	public function parse(string $value)
	{
		if (! Config::get('app.use_entity')) {
			return $value;
		}
		
		$reflection = new \ReflectionClass($this->model);
		$property = $reflection->getProperty($value);
	
		if ($property->getName() === $value) {
			foreach ($property->getAttributes() as $attribute) {
				if ($attribute->getName() === Column::class) {
					return $attribute->getArguments()['name'];
				}
			}
		}
		
		return $value;
	}
}
