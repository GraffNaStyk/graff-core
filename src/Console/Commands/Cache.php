<?php

namespace App\Facades\Console\Commands;

use App\Facades\Console\ArgvParser;
use App\Facades\Console\Command;
use App\Facades\Storage\Storage;

class Cache extends Command
{
	public static string $name = 'app:clear:cache';
	
	public function __construct(ArgvParser $argvParser, protected Storage $storage)
	{
		$this->parser = $argvParser;
		parent::__construct();
	}
	
	public function execute(): int
	{
		$this->output('Execute command...', 'green');
		
		if ($this->storage->remove('/var/cache')) {
			$this->output('Cache cleared', 'green');
		}
		
		return Command::SUCCESS;
	}
}
