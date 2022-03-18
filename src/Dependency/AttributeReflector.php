<?php

namespace App\Facades\Dependency;

use App\Facades\Property\PropertyFacade;

class AttributeReflector
{
	use PropertyFacade;
	
	public function reflect(\ReflectionClass|\ReflectionProperty $reflection): ?self
	{
		$attributes = [];
		
		if (empty($reflection->getAttributes())) {
			return null;
		}
		
		foreach ($reflection->getAttributes() as $attribute) {
			foreach ($attribute->getArguments() as $key => $value) {
				$attributes[$key] = $value;
			}
		}
		
		$this->setParams($attributes);
		
		return $this;
	}
}
