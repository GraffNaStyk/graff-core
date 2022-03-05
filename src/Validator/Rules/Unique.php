<?php

namespace App\Facades\Validator\Rules;

use App\Facades\Db\Model;

class Unique extends Rule
{
	/**
	 * @var Model
	 */
	private Model $model;
	
	public function __construct(
		string $model,
		private string $compareField,
		?string $description = null,
		private string $compareType = '=',
	)
	{
		$this->model       = new $model;
		$this->description = $description ?? 'This value exist now';
	}
	
	public function run(): bool
	{
		return ! (bool) $this->model->select()->where($this->compareField, $this->compareType, $this->field)->exist();
	}
}
