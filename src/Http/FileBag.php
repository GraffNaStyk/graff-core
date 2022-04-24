<?php

namespace App\Facades\Http;

class FileBag
{
	private ?array $files = null;
	
	private bool $multiple = false;
	
	private const FILE_KEYS = ['name', 'type', 'tmp_name', 'error', 'size'];
	
	public function __construct(array $files)
	{
		$this->normalize((array) reset($files));
	}
	
	private function normalize(array $file): void
	{
		if (empty($file)) {
			return;
		}

		if (is_array($file['name'])) {
			$this->multiple = true;
			$this->refactor($file);
		} else {
			$this->setFile($file);
		}
	}
	
	private function refactor(array $files): void
	{
		$count = count($files['name']);
		
		for ($i = 0; $i < $count; $i++) {
			foreach (self::FILE_KEYS as $key) {
				$result[$key] = $files[$key][$i];
			}
			
			$this->setFile($result);
			unset($result);
		}
	}
	
	private function setFile(array $file): void
	{
		unset($file['full_path']);
		
		if (self::FILE_KEYS === array_keys($file) && UPLOAD_ERR_NO_FILE !== (int) $file['error']) {
			if ($this->isMultiple()) {
				$this->files[] = $file;
			} else {
				$this->files = $file;
			}
		}
	}
	
	public function isMultiple(): bool
	{
		return $this->multiple;
	}
	
	public function get():? array
	{
		return $this->files;
	}
	
	public function hasFiles(): bool
	{
		return ! ($this->files === null);
	}
}
