<?php

namespace App\Facades\Html;

use App\Facades\Db\Db;
use App\Facades\Http\View;

class Pagination
{
	public static function make(string $model, int $page, string $url)
	{
		$total = ceil($model::count('id')->get()->count / Db::PER_PAGE);
		
		View::set([
			'pagination' => [
				'previousLink' => $url.'/'.($page - 1 === 0 ? 1 : $page - 1),
				'nextLink' => $url.'/'.($page + 1),
				'previous' => ($page - 1 === 0 ? 1 : $page - 1),
				'next' => ($page + 1),
				'currentPage' => ($page),
				'total' => $total,
				'first' => $url.'/1',
				'last' => $url.'/'.$total,
				'current' => $url.'/'.($page)
			]
		]);
	}
}
