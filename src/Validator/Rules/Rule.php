<?php

namespace App\Facades\Validator\Rules;

use App\Facades\Property\Bag;

abstract class Rule
{
	protected mixed $field;
	
	protected string $key;
	
	protected string $description;
	
	private static ?Bag $requestBag = null;

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
	
	public static function setRequestBag(array $params): void
	{
		if (self::$requestBag === null) {
			self::$requestBag = new Bag($params);
		}
	}
	
	public function getRequestBag(): ?Bag
	{
		return self::$requestBag;
	}
}
