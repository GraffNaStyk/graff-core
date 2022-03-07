<?php

namespace App\Facades\Validator;

class Validator implements ValidatorInterface
{
	private static array $errors = [];
	
	public static function validate(array $request, array $rules): bool
	{
		$errors = [];
		
		foreach ($rules as $key => $rule) {
			foreach ($rule as $eachRule) {
				$eachRule->setRequestBag($request);
				$eachRule->setField($request[$key]);
				$eachRule->setKey($key);

				if (! $eachRule->run()) {
					$errors[] = ['field' => $key, 'msg' => $eachRule->getErrorMessage()];
				}
			}
		}

		static::$errors = $errors;

		return empty(array_filter(static::$errors));
	}
	
	public static function getErrors(): array
	{
		return static::$errors;
	}
	
	public static function setErrors(array $errors): void
	{
		static::$errors = $errors;
	}
}
