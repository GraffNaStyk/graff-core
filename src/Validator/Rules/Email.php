<?php

namespace App\Facades\Validator\Rules;

class Email extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Must be a email';
	}
	
	public function run(): bool
	{
		return filter_var($this->field, FILTER_VALIDATE_EMAIL);
	}
}
