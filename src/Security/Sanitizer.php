<?php

namespace App\Facades\Security;

use App\Facades\Config\Config;
use App\Facades\Validator\Type;

class Sanitizer
{
	private array $regex = [
		'/[\x{1F600}-\x{1F64F}]/u',
		'/[\x{1F300}-\x{1F5FF}]/u',
		'/[\x{1F680}-\x{1F6FF}]/u',
		'/[\x{2600}-\x{26FF}]/u',
		'/[\x{2700}-\x{27BF}]/u',
	];

	public function clear(mixed $item): string|int|bool|array|null|float|object
	{
		if (! is_numeric($item)) {
			$item = rawurldecode($item);
		}

		return Type::get($item);
	}
}