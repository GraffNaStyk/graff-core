<?php

namespace App\Facades\Validator\Rules;

class NotEmptyJson extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Must be a json';
	}
	
	public function run(): bool
	{
		$field = json_decode($this->field, true);
		
		return ! empty($field);
	}
}
