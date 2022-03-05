<?php

namespace App\Facades\Validator\Rules;

class Required extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Is required';
	}
	
	public function run(): bool
	{
		return isset($this->field);
	}
}
