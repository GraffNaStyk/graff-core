<?php

namespace App\Facades\Validator\Rules;

class Allowed extends Rule
{
	public function __construct(string $description, private array $items)
	{
		$this->description = $description;
	}
	
	public function run(): bool
	{
		return in_array($this->field, $this->items, true);
	}
}
