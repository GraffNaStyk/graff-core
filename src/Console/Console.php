<?php

namespace App\Facades\Console;

use App\Facades\Dependency\Container;
use App\Facades\Dependency\ContainerBuilder;
use ReflectionClass;

class Console
{
	const COMMAND_DIR = 'app/commands';
	const COMMAND_NAMESPACE = 'App\\Commands\\';
	const FACADE_COMMAND_NAMESPACE = 'App\\Facades\\Console\\Commands\\';
	
	private ArgvParser $parser;
	
	private ContainerBuilder $builder;
	
	private static ?string $commandName = null;
	
	private static bool $createInterface = false;
	
	private static string $facadeCommandDir;
	
	public function __construct(ArgvParser $argvParser)
	{
		self::$facadeCommandDir = __DIR__.'/Commands';

		$this->parser  = $argvParser;
		$this->builder = new ContainerBuilder(new Container());
		$this->builder->container->add(ArgvParser::class, $this->parser);

		$this->parser->parse();
		$this->configure();
	}
	
	private function configure(): void
	{
		$showTips = true;
		
		if (is_dir(app_path(self::COMMAND_DIR))) {
			$objects = [
				...array_diff(scandir(app_path(self::COMMAND_DIR)), ['.', '..']),
				...array_diff(scandir(self::$facadeCommandDir), ['.', '..']),
			];
		} else {
			$objects = [
				...array_diff(scandir(self::$facadeCommandDir), ['.', '..']),
			];
		}
		
		foreach ($objects as $object) {
			$object = $this->getObjectName($object);

			if (property_exists($object, 'name') && $this->parser->has($object::$name)) {
				$reflector = new ReflectionClass($object);
				$showTips  = false;

				static::setCommandName($object::$name);
				call_user_func_array([$reflector, 'newInstance'], $this->builder->getConstructorParameters($reflector));
				break;
			}
		}
		
		if ($showTips) {
			foreach ($objects as $object) {
				$object = $this->getObjectName($object);
				
				if (property_exists($object, 'name')) {
					$text = $object::$name;
					
					if (method_exists($object, 'getDescription')) {
						$text .= '                                        '.$object::getDescription();
					}
					
					echo $text."\n";
				}
			}
		}
	}
	
	private function getObjectName(string $object): string
	{
		if (str_contains($object, 'Command') && $object !== 'Command.php') {
			return self::COMMAND_NAMESPACE.str_replace('.php', '', $object);
		} else {
			return self::FACADE_COMMAND_NAMESPACE.str_replace('.php', '', $object);
		}
	}
	
	private static function setCommandName(string $commandName): void
	{
		static::$commandName = $commandName;
	}
	
	public static function getCommandName(): ?string
	{
		return static::$commandName;
	}
}
