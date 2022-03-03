<?php

namespace App\Facades\Validator\Rules;

use App\Facades\Db\Model;

class Unique extends Rule
{
	/**
	 * @var Model
	 */
	private Model $model;
	
	public function __construct(string $model, private string $compareField)
	{
		$this->model = new $model;
	}
	
	public function run(): bool
	{
		return (bool) $this->model->select()->where($this->compareField, '=', $this->field)->exist();
	}
}
