<?php

namespace App\Facades\Helpers;

use App\Facades\Http\Router\Router;
use App\Facades\Http\View;
use App\Facades\Url\Url;

trait JavaScriptLoader
{
	protected string $jsDir = '/js';
	protected string $loadType = 'application/javascript';
	
	public function loadJs(... $scripts): void
	{
		$loaded = [];
		
		foreach ($scripts as $item) {
			$loaded[] = trim('<script type="'.$this->loadType.'" src="'.Url::full().$this->jsDir.$item.'.js"></script>');
		}
		
		View::set(['js' => $loaded]);
	}
	
	public function loadJsFromDir(string $dir): void
	{
		if (! is_dir(js_path($dir))) {
			throw new \LogicException('Directory '.js_path($dir).' not exist!');
		}
		
		$loaded = [];
		
		foreach (new \DirectoryIterator(js_path($dir)) as $item) {
			if ($item->getExtension() === 'js' && $item->isReadable()) {
				$loaded[] = trim('<script type="'.$this->loadType.'" src="'.Url::full().$this->jsDir.$dir.'/'.$item->getBaseName().'"></script>');
			}
		}
		
		View::set(['js' => $loaded]);
	}
	
	protected function enableJsAutoload(): void
	{
		$loaded = null;
		$class  = Str::toLineSeparator(Router::getClass());
		$action = Str::toLineSeparator(Router::getAction());
		
		if (is_readable(js_path(Router::getAlias().'/'.$class.'/'.$action.'.js'))) {
			$loaded = trim('<script type="'.$this->loadType.'" src="'.
				Url::full().$this->jsDir.'/'.Router::getAlias().'/'.$class.'/'.$action.'.js"></script>'
			);
		}
		
		if ($loaded !== null) {
			View::set(['js' => [$loaded]]);
		}
	}
	
	public function setJsDir(string $jsDir): void
	{
		$this->jsDir = $jsDir;
	}
	
	public function setJsLoadType(string $loadType): void
	{
		$this->loadType = $loadType;
	}
}
