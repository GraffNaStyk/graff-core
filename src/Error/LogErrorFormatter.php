<?php

namespace App\Facades\Error;

use App\Facades\Http\Router\Router;

class LogErrorFormatter
{
	public function __construct(
		private \Throwable $throwable,
		private Router $router
	) {}
	
	public function format()
	{
		echo '<style>
        * {
            background: #252e39;
            color: #fff;
            font-family: \'Nunito\', sans-serif;
            text-align: justify;
            font-weight: bold;
        } </style>';
		
		echo '<link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">';
		echo '<p style="line-height: 30px; text-align: justify">'.$this->throwable->getMessage().'</p>';
		echo '<p> Stack trace: </p>';
		
		pd($this->throwable->getTraceAsString(), false);
		
		pd('File: &nbsp;'. $this->throwable->getFile(), false);
		pd('Line: '.$this->throwable->getLine(), false);
		
		pd('Router info: ', false);
		pd($this->router->routeParams());
	}
}
