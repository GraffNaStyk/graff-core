<?php

namespace App\Facades\Console\Commands;

use App\Facades\Console\ArgvParser;
use App\Facades\Console\Command;
use App\Facades\Storage\Storage;

class Log extends Command
{
	public static string $name = 'app:clear:logs';
	
	public function __construct(ArgvParser $argvParser, protected Storage $storage)
	{
		$this->parser = $argvParser;
		parent::__construct();
	}
	
	public function execute(): int
	{
		$this->storage->remove('/var/logs');
		return Command::SUCCESS;
	}
}
