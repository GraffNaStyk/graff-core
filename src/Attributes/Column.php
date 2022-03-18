<?php

namespace App\Attributes\Table;

use Attribute;

#[Attribute]
class Column
{
	public function __construct(public string $name, public ?string $type = null){}
}
