<?php

namespace App\Facades\TwigExt;

use App\Facades\Http\Router\Route;
use App\Facades\Http\Router\Router;
use App\Facades\Http\Session;
use App\Facades\Url\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExt extends AbstractExtension
{
    public function getFunctions(): array
    {
        parent::getFunctions();
        return [
            $this->print(),
            $this->csrf(),
            $this->url(),
            $this->tooltip(),
            $this->options(),
            $this->route(),
            $this->assets(),
	        $this->img_url(),
	        $this->public_path()
        ];
    }

    public function csrf(): TwigFunction
    {
        return new TwigFunction('csrf', function ($path) {
            echo Session::get('@csrf.'.$path);
        });
    }

    public function print(): TwigFunction
    {
        return new TwigFunction('print', function ($item) {
            echo '<pre>';
            print_r($item);
            echo '</pre>';
        });
    }

    public function assets(): TwigFunction
    {
        return new TwigFunction('assets', function ($url) {
            echo Route::checkProtocol().'://'.getenv('HTTP_HOST').Url::base().'/public/assets'.$url;
        });
    }
	
	public function public_path(): TwigFunction
	{
		return new TwigFunction('public_path', function ($url) {
			echo Route::checkProtocol().'://'.getenv('HTTP_HOST').Url::base().'/public/'.$url;
		});
	}

    public function url(): TwigFunction
    {
        return new TwigFunction('url', function ($url = null) {
            if (Router::getAlias() === 'http') {
                echo Route::checkProtocol().'://'.getenv('HTTP_HOST').Url::base().$url;
            } else {
                echo Route::checkProtocol().'://'.getenv('HTTP_HOST').Url::base().'/'.Router::getAlias().$url;
            }
        });
    }
	
	public function route(): TwigFunction
	{
		return new TwigFunction('route', function ($route, $params = []) {
			$url = Url::full().Url::get().Route::urls()[$route];
			
			if (! empty($params)) {
				foreach ($params as $key => $param) {
					$url = str_replace('{'.$key.'}', $param, $url);
				}
			}
			
			echo $url;
		});
	}

    public function tooltip(): TwigFunction
    {
        return new TwigFunction('tooltip', function ($text, $placement = 'top') {
            echo 'data-toggle="tooltip" title="'.$text.'" data-placement="'.$placement.'"';
        });
    }

    public function options(): TwigFunction
    {
        return new TwigFunction('options', function ($options = []) {
            echo htmlspecialchars(json_encode($options));
        });
    }
	
	public function img_url(): TwigFunction
	{
		return new TwigFunction('uri_img', function ($url = null) {
			echo Route::checkProtocol().'://'.getenv('HTTP_HOST').Url::base().$url;
		});
	}
}
