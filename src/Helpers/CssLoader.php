<?php

namespace App\Facades\Helpers;

use App\Facades\Http\Router\Router;
use App\Facades\Http\View;
use App\Facades\Url\Url;

trait CssLoader
{
	protected string $cssDir = '/css';
	
	public function loadCss(... $scripts): void
	{
		$loaded = [];
		
		foreach ($scripts as $item) {
			$loaded[] = trim('<link rel="stylesheet" href="'.Url::full().$this->cssDir.$item.'.js">');
		}
		
		View::set(['css' => $loaded]);
	}
	
	public function loadCssFromDir(string $dir): void
	{
		if (! is_dir(css_path($dir))) {
			throw new \LogicException('Directory '.css_path($dir).' not exist!');
		}
		
		$loaded = [];

		foreach (new \DirectoryIterator(css_path($dir)) as $item) {
			if ($item->getExtension() === 'css') {
				$loaded[] = trim('<script type="application/javascript" src="'.Url::full().$this->cssDir.$dir.'/'.$item->getBaseName().'"></script>');
			}
		}

		View::set(['css' => $loaded]);
	}
	
	protected function enableCssAutoload(): void
	{
		$loaded = null;
		
		if (is_readable(css_path(Router::getAlias().'/'.Router::getClass().'/'.Router::getAction().'.css'))) {
			$loaded = trim('<link rel="stylesheet" href="'.
				Url::full().'/'.str_replace(
					app_path(),
					'',
					css_path(Router::getAlias().'/'.Router::getClass().'/'.Router::getAction())
				).
				'.css">'
			);
		}

		if ($loaded !== null) {
			View::set(['css' => [$loaded]]);
		}
	}
	
	public function setCssDir(string $cssDir): void
	{
		$this->cssDir = $cssDir;
	}
}
