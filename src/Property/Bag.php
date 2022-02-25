<?php

namespace App\Facades\Property;

class Bag
{
	protected array $bag;
	
	public function __construct(array $parameters)
	{
		$this->bag = $parameters;
	}
	
	public function has(?string $offset): bool
	{
		return Has::check($this->bag, $offset);
	}
	
	public function get(?string $offset=null): mixed
	{
		if ($offset === null) {
			return $this->bag;
		}
		
		return Get::check($this->bag, $offset);
	}
	
	public function all(): mixed
	{
		return $this->get();
	}
}
