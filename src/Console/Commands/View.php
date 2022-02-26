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
		
		$view = $this->input('Insert view name: ');
		
		Dir::create(view_path(strtolower($this->parser->get('ns')).'/'.ucfirst($this->parser->get('d'))));
		
		if (! is_readable(
			view_path(
				strtolower($this->parser->get('ns')).'/'.ucfirst($this->parser->get('d')).'/'.$view.'.twig'
			)
		)) {
			file_put_contents(
				view_path(
					strtolower($this->parser->get('ns')).'/'.ucfirst($this->parser->get('d')).'/'.$view.'.twig'
				),
				file_get_contents(src_path('/Console/Files/view'))
			);
		}
		
		if ($this->input('Create Javascript file?') === 'y') {
			if (! is_readable(
				js_path(
					strtolower($this->parser->get('ns')).'/'.strtolower($this->parser->get('d')).'/'.Str::toLineSeparator($view).'.js'
				)
			)) {
				Dir::create(js_path(strtolower($this->parser->get('ns')).'/'.strtolower($this->parser->get('d'))));
				
				file_put_contents(
					js_path(
						strtolower($this->parser->get('ns')) .'/'.strtolower($this->parser->get('d')).'/'.Str::toLineSeparator($view).'.js'
					),
					'');
			}
		}
		
		if ($this->input('Create css file?') === 'y') {
			if (! is_readable(
				css_path(
					strtolower($this->parser->get('ns')).'/'.strtolower($this->parser->get('d')).'/'.Str::toLineSeparator($view).'.css'
				)
			)) {
				Dir::create(css_path(strtolower($this->parser->get('ns')).'/'.strtolower($this->parser->get('d'))));
				
				file_put_contents(
					css_path(
						strtolower($this->parser->get('ns')).'/'.strtolower($this->parser->get('d')).'/'.Str::toLineSeparator($view).'.css'
					),
					'');
			}
		}
		
		return Command::SUCCESS;
	}
}
