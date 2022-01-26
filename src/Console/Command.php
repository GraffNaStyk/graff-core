<?php

namespace App\Facades\Console;

use App\Facades\Config\Config;
use App\Facades\Security\Sanitizer;
use App\Facades\Helpers\Dir;
use JetBrains\PhpStorm\NoReturn;

class Command implements CommandInterface
{
	const SUCCESS = 1;
	const FAILED  = 0;
	const ABORTED = 2;
	
	protected ArgvParser $parser;
	
	private Sanitizer $sanitizer;
	
	private array $backgrounds = [
		'white'      => 97,
		'red'        => 31,
		'green'      => 32,
		'yellow'     => 33,
		'blue'       => 34,
		'magenta'    => 35,
		'cyan'       => 36,
		'light grey' => 37,
	];
	
	private string $fileNamespace = '/';
	
	public function __construct()
	{
		$this->configure();
	}
	
	public function configure(): void
	{
		ini_set('display_startup_errors', 1);
		error_reporting(Config::get('app.reporting_levels'));
		
		if (! method_exists($this, 'execute')) {
			$this->output('Missing execute method!', 'red')->close();
		}
		
		$this->sanitizer = new Sanitizer();
		$this->execute();
	}
	
	protected function input(string $message, string $color='white'): string
	{
		echo "\033[".$this->backgrounds[mb_strtolower($color)]."m".$message.": \033[0m";
		$handle = fopen('php://stdin','r');
		$result = fgets($handle);
		fclose($handle);

		echo "\n";

		return str_replace(["\n", "\r"], [''], $this->sanitizer->clear($result));
	}

	protected function output(string $message, string $color='white'): Command
	{
		echo "\033[".$this->backgrounds[mb_strtolower($color)]."m".$message."\033[0m \n";
		return $this;
	}
	
	#[NoReturn] protected function close(): void
	{
		exit();
	}
	
	public function putFile(string $path, string $name, string $content): void
	{
		Dir::create(app_path($path).$this->fileNamespace);
		
		$fullPath = str_replace('//', '/', app_path($path.$this->fileNamespace.'/'.ucfirst($name).'.php'));

		if (is_readable($fullPath)) {
			$this->output('File '.$fullPath.' exist', 'red')->close();
		}

		$content = str_replace('CLASSNAME', ucfirst($name), $content);
		$tmp     = array_filter(explode('/', $this->fileNamespace));
		
		$namespace = '\\';

		foreach ($tmp as $ns) {
			$namespace .= ucfirst($ns).'\\';
		}
		
		$namespace = rtrim($namespace, '\\');
		$content   = str_replace('\\NAMESPACE', $namespace, $content);

		if (file_put_contents($fullPath, $content)) {
			$this->output(
				'File:'.$fullPath.' created',
				'green'
			);
		} else {
			$this->output(
				'Cannot create file: '.app_path($path.'/'.ucfirst($name)).'.php',
				'red'
			)->close();
		}
	}

	public function getFile(string $name): string
	{
		return file_get_contents(__DIR__.'/files/'.$name);
	}
	
	protected function setNamespace(ArgvParser $argvParser): void
	{
		if ($argvParser->has('ns')) {
			$this->fileNamespace .= strtolower(trim(ltrim($argvParser->get('ns'), '/')));
		}
	}
}
