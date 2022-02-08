<?php

namespace App\Facades\Property;

use App\Facades\Validator\Type;

class Bag
{
	private array $bag;
	
	public function __construct(array $parameters)
	{
		$this->bag = $parameters;
	}
	
	public function has(?string $offset): bool
	{
		return Has::check($this->bag, $offset);
	}
	
	public function get(?string $offset=null)
	{
		if ($offset === null) {
			return $this->bag;
		}
		
		return Get::check($this->bag, $offset);
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
