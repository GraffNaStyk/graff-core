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
			$loaded[] = trim('<link rel="stylesheet" href="'.Url::full().$this->cssDir.$item.'.css">');
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
				$loaded[] = trim('<link rel="stylesheet" href="'.Url::full().$this->cssDir.$dir.'/'.$item->getBaseName().'">');
			}
		}

		View::set(['css' => $loaded]);
	}
	
	protected function enableCssAutoload(): void
	{
		$loaded = null;
		$class  = Str::toLineSeparator(Router::getClass());
		
		if (is_readable(css_path(Router::getAlias().'/'.$class.'/'.Router::getAction().'.css'))) {
			$loaded = trim('<link rel="stylesheet" href="'.
				Url::full().$this->cssDir.'/'.Router::getAlias().'/'.$class.'/'.Router::getAction(). '.css">'
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
