<?php

namespace App\Facades\Validator\Rules;

class Date extends Rule
{
	protected string $format = 'Y-m-d H:i:s';
	
	public function __construct(string $format = 'Y-m-d H:i:s', ?string $description = null)
	{
		$this->format      = $format;
		$this->description = $description ?? 'Wrong date format';
	}
	
	public function run(): bool
	{
		if ($this->field === null) {
			return false;
		}
		
		$dt = \DateTime::createFromFormat($this->format, $this->field);
		
		return $dt && $dt->format($this->format) === $this->field;
	}
}
