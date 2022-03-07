<?php

namespace App\Facades\Validator\Rules;

use App\Facades\Property\Bag;

abstract class Rule
{
	protected mixed $field;
	
	protected string $key;
	
	protected string $description;
	
	protected Bag $requestParams;

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
	
	public function setRequestBag(array $params)
	{
		$this->requestParams = new Bag($params);
	}
}
