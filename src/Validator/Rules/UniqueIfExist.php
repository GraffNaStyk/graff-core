<?php

namespace App\Facades\Validator\Rules;

use App\Facades\Db\Model;

class UniqueIfExist extends Rule
{
	/**
	 * @var Model
	 */
	private Model $model;
	
	public function __construct(
		string $description,
		string $model,
		private string $compareField,
		private string $compareType = '='
	)
	{
		$this->model       = new $model;
		$this->description = $description;
	}
	
	public function run(): bool
	{
		return ! (bool) $this->model->select()
			->where($this->compareField, $this->compareType, $this->field)
			->where($this->compareField, '<>', $this->field)
			->exist();
	}
}
