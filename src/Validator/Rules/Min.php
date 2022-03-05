<?php

namespace App\Facades\Validator\Rules;

class Min extends Rule
{
	private int $min;
	
	public function __construct(int $min, ?string $description = null)
	{
		$this->min         = $min;
		$this->description = $description ?? 'Min value is '.$this->min;
	}

	public function run(): bool
	{
		return $this->field >= $this->min;
	}
}
