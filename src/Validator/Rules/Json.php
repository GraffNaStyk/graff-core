<?php

namespace App\Facades\Validator\Rules;

class Json extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Must be a json';
	}
	
	public function run(): bool
	{
		json_decode($this->field);
		
		return json_last_error() !== JSON_ERROR_NONE;
	}
}
