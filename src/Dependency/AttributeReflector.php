<?php

namespace App\Facades\Dependency;

class AttributeReflector
{
	public function reflect(\ReflectionClass $reflectionClass)
	{
		if (empty($reflectionClass->getAttributes())) {
			return null;
		}
		
		
		dd($reflectionClass->getAttributes());
	}
}
