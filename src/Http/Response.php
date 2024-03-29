<?php

namespace App\Facades\Http;

use App\Facades\Config\Config;
use App\Facades\Http\Router\Route;

class Response
{
	public const RESPONSE_CODES = [
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		500 => 'Internal Server Error'
	];
	
	private array $data = [];
	
	private ?string $content = null;
	
	private bool $isJsonResponse = false;
	
	private bool $isXmlResponse = false;
	
	private bool $isRedirectResponse = false;
	
	private bool $isFileResponse = false;
	
	private bool $isDownloadResponse = false;
	
	private array $customHeaders = [];
	
	private array $cookies = [];
	
	private array $redirectData = [];
	
	private int $responseCode = 200;
	
	public function setHeader(string $name, string $value): self
	{
		$this->customHeaders[$name] = $value;
		
		return $this;
	}
	
	public function setHeaders(array $headers): self
	{
		foreach ($headers as $key => $header) {
			$this->setHeader($key, $header);
		}
		
		return $this;
	}
	
	public function json(): self
	{
		$this->setHeader('Content-type', 'application/json;charset=utf-8');
		$this->isJsonResponse = true;
		
		return $this;
	}
	
	public function xml(): self
	{
		$this->isXmlResponse = true;
		$this->setHeader('Content-type', 'text/xml;charset=utf-8');
		
		return $this;
	}
	
	public function file(string $file): self
	{
		$this->setHeader('Content-Type', mime_content_type($file));
		$this->setHeader('Content-Length', filesize($file));

		$this->isFileResponse = true;
		$this->content        = $file;

		return $this;
	}
	
	public function download(string $file): self
	{
		$this->setHeaders(
			[
				'Content-Type'              => mime_content_type($file),
				'Cache-Control'             => 'private',
				'Content-Transfer-Encoding' => 'Binary',
				'Content-Length'            => filesize($file),
				'Content-Disposition'       => 'attachment; filename='.basename($file)
			]
		);

		$this->isDownloadResponse = true;
		$this->content            = $file;

		return $this;
	}
	
	public function redirect(?string $path, int $code = 302, bool $direct = false): self
	{
		$this->isRedirectResponse = true;
		$this->redirectData       = ['path' => $path, 'code' => $code, 'direct' => $direct];
		$this->responseCode       = $code;
		
		return $this;
	}
	
	public function setData(array $data): self
	{
		$this->data	= $data;
		
		return $this;
	}
	
	public function setContent(string $content): self
	{
		$this->content = $content;
		
		if (! isset($this->customHeaders['Content-type'])) {
			$this->setHeader('Content-type', 'text/html;charset=utf-8');
		}
		
		return $this;
	}
	
	public function send(): self
	{
		return $this;
	}
	
	public function setCode(int $code = 200): self
	{
		$this->responseCode = $code;
		
		return $this;
	}
	
	public function setCookie(string $name, string $value, int $seconds = 60): self
	{
		$this->cookies[$name] = ['value' => $value, 'time' => $seconds];
		
		return $this;
	}
	
	public function setCookies(array $cookies): self
	{
		foreach ($cookies as $key => $cookie) {
			$this->setCookie($key, is_array($cookie) ? $cookie['value'] : $cookie, $cookie['time']);
		}
		
		return $this;
	}
	
	public function prepareHeaders(): void
	{
		if (Config::get('app.dev') && is_array(Config::get('headers.dev'))) {
			$this->setHeaders(Config::get('headers.dev'));
		} else if (! Config::get('app.dev') && is_array(Config::get('headers.prod'))) {
			$this->setHeaders(Config::get('headers.prod'));
		} else {
			$this->setHeaders(Config::get('headers'));
		}
	}
	
	public function getResponse(): ?string
	{
		if (! headers_sent()) {
			$this->prepareHeaders();
			http_response_code($this->responseCode);
			
			if (! empty($this->customHeaders)) {
				foreach ($this->customHeaders as $name => $value) {
					\header($name.': '.$value);
				}
			}
		}
		
		if (! empty($this->cookies)) {
			foreach ($this->cookies as $key => $cookie) {
				Cookie::flash($key, $cookie['value'], $cookie['time']);
			}
		}
		
		ob_clean();
		
		if ($this->isFileResponse || $this->isDownloadResponse) {
            ob_start();
			return readfile($this->content);
		}
		
		if ($this->isRedirectResponse) {
			Route::redirect($this->redirectData['path'], $this->redirectData['code']);
			exit;
		}
		
		if ($this->content !== null || $this->isXmlResponse) {
			return $this->content;
		}
		
		if ($this->isJsonResponse) {
			return json_encode($this->data);
		}

		throw new \LogicException('Wrong response send! Set response content or response data');
	}
}
