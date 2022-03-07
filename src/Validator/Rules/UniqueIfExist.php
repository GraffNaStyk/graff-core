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
		string $model,
		private string $compareToField = 'id',
		?string $description = null,
		private string $compareType = '='
	)
	{
		$this->model       = new $model;
		$this->description = $description ?? 'This value exist now';
	}

	public function run(): bool
	{
		if (! $this->getRequestBag()->has($this->compareToField)) {
			return false;
		}

		return ! (bool) $this->model->select()
			->where($this->key, $this->compareType, $this->field)
			->where($this->compareToField, '<>', $this->getRequestBag()->get($this->compareToField))
			->exist();
	}
}
