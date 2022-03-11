<?php

namespace App\Facades\Validator\Rules;

class NotNull extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Cannot be null';
	}
	
	public function run(): bool
	{
		return $this->field !== null;
	}
}
