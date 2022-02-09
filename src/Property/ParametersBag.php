<?php

namespace App\Facades\Property;

use App\Facades\Validator\Type;

class ParametersBag extends Bag
{
	public function __construct(array $parameters)
	{
		parent::__construct($parameters);
	}
	
	public function set($data, ?string $offset): void
	{
		$this->bag = array_merge($this->bag, Set::set($this->bag, Type::get($data), $offset));
	}
	
	public function remove(?string $offset): void
	{
		$this->bag = Remove::remove($this->bag, $offset);
	}
}
