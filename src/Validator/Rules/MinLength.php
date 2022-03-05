<?php

namespace App\Facades\Validator\Rules;

class MinLength extends Rule
{
	private int $length;
	
	public function __construct(int $length, ?string $description = null)
	{
		$this->length      = $length;
		$this->description = $description ?? 'Min length is '.$this->length;
	}
	
	public function run(): bool
	{
		return strlen($this->field) >= $this->length;
	}
}
