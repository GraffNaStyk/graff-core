<?php

namespace App\Facades\Html;

use App\Facades\Db\Db;
use App\Facades\Http\Router\RouteGenerator;
use App\Facades\Http\View;

class Pagination
{
	public static function make(string $model, int $page, string $url, array $params = [])
	{
		if (! empty($params)) {
			$total = $model::count('id');
			
			foreach ($params as $key => $param) {
				if ($param) {
					$total->where($key, '=', $param);
				}
			}
			
			$total = ceil($total->get()->count / Db::PER_PAGE);
		} else {
			$total = ceil($model::count('id')->get()->count / Db::PER_PAGE);
		}
		
		View::set([
			'pagination' => [
				'previousLink' => RouteGenerator::generate($url, ['page' => ($page - 1 === 0 ? 1 : $page - 1)], $params),
				'nextLink'     => RouteGenerator::generate($url, ['page' => ($page + 1)], $params),
				'previous'     => ($page - 1 === 0 ? 1 : $page - 1),
				'next'         => ($page + 1),
				'currentPage'  => ($page),
				'total'        => $total,
				'first'        => RouteGenerator::generate($url, ['page' => 1], $params),
				'last'         => RouteGenerator::generate($url, ['page' => $total], $params),
				'current'      => RouteGenerator::generate($url, ['page' => $page], $params),
				'page'         => $page
			]
		]);
	}
}
