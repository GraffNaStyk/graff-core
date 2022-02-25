<?php

namespace App\Facades\Dependency;

use App\Facades\Property\PropertyFacade;

class AttributeReflector
{
	use PropertyFacade;
	
	public function reflect(\ReflectionClass $reflectionClass): ?self
	{
		$attributes = [];
		
		if (empty($reflectionClass->getAttributes())) {
			return null;
		}
		
		foreach ($reflectionClass->getAttributes() as $attribute) {
			foreach ($attribute->getArguments() as $key => $value) {
				$attributes[$key] = $value;
			}
		}
		
		$this->setParams($attributes);
		
		return $this;
	}
}
