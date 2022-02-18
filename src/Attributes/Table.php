<?php

namespace App\Attributes\Table;

use Attribute;

#[Attribute]
class Table
{
	public function __construct(public string $table, public bool $isTriggered = false){}
}
