<?php

namespace App\Facades\Validator\Rules;

class Double extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Only real numbers.';
	}
	
	public function run(): bool
	{
		return is_numeric($this->field) || is_float($this->field);
	}
}
