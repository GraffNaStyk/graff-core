<?php

namespace App\Facades\Validator\Rules;

class Text extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Only letters';
	}
	
	public function run(): bool
	{
		return ! is_numeric($this->field);
	}
}
