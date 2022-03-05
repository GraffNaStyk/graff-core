<?php

namespace App\Facades\Validator\Rules;

abstract class Rule
{
	protected mixed $field;
	
	protected string $key;
	
	protected string $description;

	public abstract function run(): bool;
	
	public function setField(mixed $field): void
	{
		$this->field = $field;
	}
	
	public function setKey(string $key): void
	{
		$this->key = $key;
	}
	
	public function getErrorMessage(): string
	{
		return $this->description;
	}
}
