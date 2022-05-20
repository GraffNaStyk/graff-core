<?php

namespace App\Attributes\Table;

use Attribute;

#[Attribute]
class Connection
{
	public function __construct(public string $connection){}
}
