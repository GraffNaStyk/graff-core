<?php

namespace App\Facades\Http;

use App\Facades\Property\Bag;
use App\Facades\Property\Get;
use App\Facades\Property\Has;
use App\Facades\Property\PropertyFacade;
use App\Facades\Property\Remove;
use App\Facades\Property\Set;
use App\Facades\Security\Sanitizer;
use App\Facades\Validator\Type;

final class Request
{
	use PropertyFacade;
	use Header;
	
    protected array $file = [];

    private string $method = 'post';

    protected array $data = [];

    public Bag $headers;
    
    public Bag $server;
    
    public Bag $cookie;
    
    private Sanitizer $sanitizer;

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';
    const METHOD_OPTIONS = 'OPTIONS';
    
    public function __construct()
    {
        $this->boot();
    }

    private function boot(): void
    {
    	$this->sanitizer = new Sanitizer();
        $this->isOptionsCall();
	    $this->initialize();
        $this->setHeaders();
    }

    public function isOptionsCall(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === self::METHOD_OPTIONS) {
            self::setAllowedOptions();
            return true;
        }

        return false;
    }

    private function initialize(): void
    {
	    $this->server  = new Bag($_SERVER);
	    $this->cookie  = new Bag($_COOKIE);
	    $this->headers = new Bag($this->setHeaders());
	    
        if (isset($_FILES) && ! empty($_FILES)) {
            $this->file = $_FILES;
        }

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $this->method = 'post';
                $this->data = $_POST;
                break;
            case 'GET':
                $this->method = 'get';
                $this->data = $_GET;
                break;
            case 'DELETE':
                $this->method = 'delete';
                $this->data = (array) json_decode(file_get_contents('php://input'));
                break;
            case 'PUT':
                $this->method = 'put';
                $this->data = (array) json_decode(file_get_contents('php://input'));
                break;
        }

        if ($this->hasHeader('Content-Type')
	        && mb_strtolower($this->header('Content-Type')) === 'application/json'
        ) {
            $this->data = (array) json_decode(file_get_contents('php://input'));
        }
    }

    public function isPost(): bool
    {
        return $this->method === self::METHOD_POST;
    }

    public function isGet(): bool
    {
        return $this->method === self::METHOD_GET;
    }

    public function isDelete(): bool
    {
        return $this->method === self::METHOD_DELETE;
    }

    public function isPut(): bool
    {
        return $this->method === self::METHOD_PUT;
    }
	
	private function setHeaders(): array
	{
		$headers = [];
		
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $key => $item) {
				$headers[mb_strtolower($key)] = $item;
			}
		} else {
			$rx_http = '/\AHTTP_/';
			foreach ($_SERVER as $key => $val) {
				if (preg_match($rx_http, $key)) {
					$arh_key    = preg_replace($rx_http, '', $key);
					$rx_matches = explode('_', $arh_key);
					if (count($rx_matches) > 0 && strlen($arh_key) > 2) {
						foreach ($rx_matches as $ak_key => $ak_val) {
							$rx_matches[$ak_key] = ucfirst($ak_val);
						}
						
						$arh_key = implode('-', $rx_matches);
					}
					
					$headers[mb_strtolower($arh_key)] = $val;
				}
			}
		}
		
		return $headers;
	}

    public function header(string $header)
    {
        return $this->headers[mb_strtolower($header)] ?? false;
    }

    public function hasHeader(string $item): bool
    {
        return Has::check($this->headers(), mb_strtolower($item));
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function getData(): array
    {
        return $this->data;
    }
	
	public function sanitize(): void
	{
		foreach ($this->data as $key => $item) {
			if (is_array($item)) {
				$this->data[$key] = $this->reSanitize($item);
			} else {
				$this->data[$key] = $this->sanitizer->clear($item);
			}
		}
	}

    protected function reSanitize(array $data): array
    {
        foreach ($data as $key => $item) {
            if (is_array($item)) {
	            $data[$key] = $this->reSanitize($item);
            } else {
                $data[$key] = $this->sanitizer->clear($item);
            }
        }

        return $data;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function get(string $offset = null)
    {
        if ($offset === null) {
            return $this->data;
        }

        return Get::check($this->data, $offset);
    }

    public function input(string $item = null)
    {
        return $this->get($item);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function file(?string $file = null)
    {
        if ($file !== null && isset($this->file[$file])) {
            return $this->file[$file];
        }

        return $this->file;
    }

    public function has(string $offset): bool
    {
        return Has::check($this->data, $offset);
    }

    public function set(mixed $item, mixed $data): void
    {
        $this->data = array_merge($this->data, Set::set($this->data, Type::get($data), $item));
    }

    public function remove(string $offset): void
    {
        $this->data = Remove::remove($this->data, $offset);
    }
	
	public static function isAjax(): bool
	{
		if (php_sapi_name() === 'cli') {
			return false;
		}
		
		$headers = getallheaders();
		
		if (isset($headers['Is-Fetch-Request'])
			&& mb_strtolower($headers['Is-Fetch-Request']) === 'true'
		) {
			return true;
		}
		
		return false;
	}

    public function __destruct()
    {
        Session::checkIfDataHasBeenProvided($this->data);
    }
}
