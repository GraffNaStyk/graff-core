<?php

namespace App\Facades\Validator\Rules;

class Nullable extends Rule
{
	public function __construct(?string $description = null)
	{
		$this->description = $description ?? 'Can be null';
	}

	public function run(): bool
	{
		return $this->field === null;
	}
}
