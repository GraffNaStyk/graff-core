<?php

namespace App\Facades\Validator\Rules;

class Integer extends Rule
{
	public function __construct(string $description)
	{
		$this->description = $description;
	}
	
	public function run(): bool
	{
		return is_numeric($this->field);
	}
}