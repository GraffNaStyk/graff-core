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
			$item = urldecode($item);
		}

		if (Config::get('app.security.enabled')) {
			$item = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $item);
			$item = preg_replace('/<noscript\b[^>]*>(.*?)<\/noscript>/is', '', $item);
			$item = preg_replace('/<a(.*?)>(.+)<\/a>/', '', $item);
			$item = preg_replace('/<iframe(.*?)>(.+)<\/iframe>/', '', $item);
			$item = preg_replace('/<img (.*?)>/is', '', $item);
			$item = preg_replace('/<embed (.*?)>/is', '', $item);
			$item = preg_replace('/<link (.*?)>/is', '', $item);
			$item = preg_replace('/<video (.*?)>(.+)<\/video>/', '', $item);
		}

		$item = strtr(
			$item,
			'��������������������������������������������������������������',
			'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
		);

		$item = preg_replace('/(;|\||`|&|^|\)|\()/i', '', $item);
		$item = preg_replace('/(\)|\(|\||&)/', '', $item);
		$item = $this->cleanEmoji($item);

		return Type::get($item);
	}

	private function cleanEmoji(mixed $text): mixed
	{
		foreach ($this->regex as $regex) {
			$text = preg_replace($regex, null, $text);
		}

		return $text;
	}
}