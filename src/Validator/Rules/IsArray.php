<?php

namespace App\Facades\Validator\Rules;

class IsArray extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Must be array.';
	}
	
	public function run(): bool
	{
		return is_array($this->field);
	}
}
