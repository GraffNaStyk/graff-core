<?php

namespace App\Facades\Http;

class FileBag
{
	private array $files;
	
	public function __construct(array $files)
	{
		$this->normalize($files);
	}
	
	private function normalize(array $files)
	{
		$fileCount = count($files['files']['name']);
		$fileKeys  = array_keys($files['files']);
		
		for ($i = 0; $i < $fileCount; $i++) {
			foreach ($fileKeys as $key) {
				$this->files[$i][$key] = $files['files'][$key][$i];
			}
		}
	}
	
	public function show()
	{
		dd($this->files);
	}
}
