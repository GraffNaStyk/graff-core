<?php

namespace App\Facades\Db;

use App\Facades\Validator\Type;

trait Builder
{
	use Variables;
	
	public function prepareValuesForSelect($values): string
	{
		$select = '';
		
		foreach ($values as $item) {
			if (is_object($item)) {
				$select .= $this->entityConverter->parse($item->getValue()).', ';
			} else {
				$select .= $this->prepareValueForWhere($item).', ';
			}
		}
		
		return rtrim($select, ', ');
	}
	
	public function prepareValueForWhere($value): string
	{
		$ret   = '';
		$value = Type::get($value);
		
		if ((bool) strpos($value, '.') === true) {
			$tmp = explode('.', $value);
			
			if ($tmp[0] === $this->trim(str_replace(['`', 'as'], '', $this->as)) && $tmp[1] === '*') {
				return '`'.$tmp[0].'`.'.$tmp[1];
			}
		}
		
		if (str_contains($value, '.') && preg_match('/( as )/', $value)) {
			$value = explode('.', $value);
			$ret .= " `{$this->trim($this->entityConverter->parse($value[0]))}`";
			$value = explode(' as ', $value[1]);
			$ret .= ".`{$this->trim($this->entityConverter->parse($value[0]))}` as `{$this->trim($value[1])}`";
		} else if (preg_match('/( as )/', $value) && ! str_contains($value, '.')) {
			$value = explode(' as ', $value);
			$ret .= "`{$this->trim($this->entityConverter->parse($value[0]))}` as `{$this->trim($value[1])}`";
		} else if (! (bool) preg_match('/( as )/', $value) && str_contains($value, '.')) {
			$value = explode('.', $value);
			$ret .= "`{$this->trim($this->entityConverter->parse($value[0]))}`.`{$this->trim($value[1])}`";
		} else {
			$ret .= "`{$this->trim($this->entityConverter->parse($value))}`";
		}
		
		return $ret;
	}
	
	public function trim($item): string
	{
		return trim($item);
	}
	
	protected function setValue(string $key, ?string $value): string
	{
		$key = $this->entityConverter->parse($key);
		
		do {
			$key = str_replace('.', '__', $key).'__'.rand(100, 10000);
		} while (isset($this->data[$key]));
		
		$this->data[$key] = Type::get($value);
		return $key;
	}
	
	protected function appendToQuery(bool $isOr = false): void
	{
		if (! $this->isFirstWhere) {
			$this->isFirstWhere = true;
			$this->query .= " WHERE ";
		} else if ($isOr) {
			$this->query .= " OR ";
		} else {
			$this->query .= " AND ";
		}
		
		if ($this->startBracket) {
			for ($i = 1; $i <= $this->startBracketCount; $i++) {
				$this->query .= '( ';
			}
			
			$this->startBracketCount = 0;
			$this->startBracket = false;
		}
	}
}
