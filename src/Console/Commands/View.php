<?php

namespace App\Facades\Console\Commands;

use App\Facades\Console\ArgvParser;
use App\Facades\Console\Command;
use App\Facades\Helpers\Dir;
use App\Facades\Helpers\Str;

class View extends Command
{
	public static string $name = 'app:make:view';
	
	public function __construct(ArgvParser $argvParser)
	{
		$this->parser = $argvParser;
		parent::__construct();
	}
	
	public static function getDescription(): string
	{
		return 'Params -d=directory -ns=namespace';
	}
	
	public function configure(): void
	{
		parent::configure();
	}
	
	protected function execute(): int
	{
		if (! $this->parser->has('d') || ! $this->parser->has('ns')) {
			$this->output('Please set view directory using -d=DIRECTORY and namespace using -ns=NAMESPACE')
				->close();
		}
		
		$view = Str::toLineSeparator($this->input('Insert view name '));
		
		if (! is_readable(
			view_path(
				strtolower($this->parser->get('ns')).'/'.ucfirst($this->parser->get('d')).'/'.$view.'.twig'
			)
		)) {
			Dir::create(view_path(strtolower($this->parser->get('ns')).'/'.ucfirst($this->parser->get('d'))));
			
			file_put_contents(
				view_path(
					strtolower($this->parser->get('ns')).'/'.ucfirst($this->parser->get('d')).'/'.$view.'.twig'
				),
				file_get_contents(src_path('/Console/Files/view'))
			);
		}
		
		if ($this->input('Create Javascript file?') === 'y') {
			$path = js_path(strtolower($this->parser->get('ns')).'/' .strtolower($this->parser->get('d')).'/');
			$file = Str::toLineSeparator($view).'.js';
			
			if (! is_readable($path.$file)) {
				Dir::create($path);
				file_put_contents($path.$file,'');
			}
		}
		
		if ($this->input('Create css file?') === 'y') {
			$path = css_path(strtolower($this->parser->get('ns')).'/'.strtolower($this->parser->get('d')).'/');
			$file = Str::toLineSeparator($view).'.css';
			
			if (! is_readable($path.$file)) {
				Dir::create($path);
				file_put_contents($path.$file,'');
			}
		}
		
		return Command::SUCCESS;
	}
}
