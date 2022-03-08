<?php

namespace App\Facades\Validator;

use App\Facades\Validator\Rules\Rule;

class Validator implements ValidatorInterface
{
	private static array $errors = [];
	
	public static function validate(array $request, array $rules): bool
	{
		$errors = [];
		Rule::setRequestBag($request);
		
		foreach ($rules as $key => $rule) {
//			foreach ($rule as $checkRule) {
//				if (! isset($request[$key]) && $checkRule instanceof Required) {
//					continue 2;
//				}
//			}
			
			foreach ($rule as $eachRule) {
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
