<?php

namespace App\Facades\Http;

use App\Facades\Config\Config;
use App\Facades\Helpers\Dir;
use App\Facades\Helpers\Str;
use App\Facades\Http\Router\Router;
use App\Facades\TwigExt\TwigExt;
use Twig;

final class View
{
    protected static ?Twig\Environment $twig = null;

    private static array $data = [];

    protected static string $ext = '.twig';

    protected static string $layout = 'http';

    public static ?string $dir = null;

    public static ?string $view = null;

    public static function render(array $data = [])
    {
        self::set($data);
        self::register();
        self::$dir = Router::getAlias();

        if (Request::isAjax()) {
        	self::$layout = 'ajax';
        }

        self::set(['layout' => '/layouts/'.self::$layout.self::$ext]);
        self::set(['ajax' => '/layouts/ajax'.self::$ext]);
        self::setView();

        if (is_readable(view_path(self::$dir.'/'.Router::getClass().'/'.self::$view.self::$ext))) {
            return self::$twig->render(self::$dir.'/'.Router::getClass().'/'.self::$view.self::$ext, self::$data);
        }

        exit(require_once view_path('/errors/view-not-found.php'));
    }
	
	public static function display(string $view, array $data = [])
	{
		self::set($data);
		self::register();
		
		if (is_readable(view_path($view))) {
			return self::$twig->render($view, self::$data);
		}
		
		exit(require_once view_path('/errors/view-not-found.php'));
	}

    private static function setView(): void
    {
        self::$view = self::$view ?? Router::getAction();
        self::$view = Str::toLineSeparator(self::$view);
    }

    public static function layout(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function change(string $view): void
    {
        self::$view = $view;
    }

    public static function set(array $data): void
    {
        foreach ($data as $key => $value) {
        	if (isset(self::$data[$key]) && is_array($value)) {
		        self::$data[$key] = array_unique([... self::$data[$key], ... $value], SORT_REGULAR);
	        } else {
		        self::$data[$key] = $value;
	        }
        }
    }

    public static function getName(): string
    {
        return self::$view;
    }

    public static function registerFunctions(): void
    {
        foreach ((new TwigExt())->getFunctions() as $fn) {
            self::$twig->addFunction($fn);
        }
    }

    public static function mail(string $template, array $data = [])
    {
        self::register();
        self::set($data);
        return self::$twig->render('mail/'.$template.self::$ext, self::$data);
    }

    private static function register(): void
    {
        if (! self::$twig instanceof Twig\Environment) {
            if (Config::get('twig.cache_view')) {
            	Dir::create(storage_path('/var/views'));
                $config['cache'] = storage_path('/var/views');
            }

            $config['debug'] = true;
            self::$twig = new Twig\Environment(new Twig\Loader\FilesystemLoader(view_path()), $config);
            
            foreach ((array) Config::get('twig.globals') as $key => $value) {
	            self::$twig->addGlobal($key, $value);
            }

            self::registerFunctions();
        }
    }
}
