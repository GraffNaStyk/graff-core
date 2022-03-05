<?php

namespace App\Facades\Validator\Rules;

class Allowed extends Rule
{
	public function __construct(private array $items, ?string $description = null)
	{
		$this->description = $description ?? 'Value not allowed';
	}
	
	public function run(): bool
	{
		return in_array($this->field, $this->items, true);
	}
}
