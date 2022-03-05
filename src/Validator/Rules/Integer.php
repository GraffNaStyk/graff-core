<?php

namespace App\Facades\Validator\Rules;

class Integer extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Must be integer.';
	}
	
	public function run(): bool
	{
		return is_numeric($this->field);
	}
}
