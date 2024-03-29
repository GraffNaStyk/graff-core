<?php

namespace App\Facades\Validator\Rules;

class Regex extends Rule
{
	private string $regex;
	
	private string $flags = 'i';
	
	public function __construct(string $regex, ?string $description = null)
	{
		$this->regex       = '/'.trim($regex, '/').'/'.$this->flags;
		$this->description = $description ?? 'Must be like pattern '.$this->regex;
	}
	
	public function run(): bool
	{
		return preg_match($this->regex, $this->field) === 1;
	}
}
