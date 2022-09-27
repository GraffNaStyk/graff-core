<?php

namespace App\Facades\Http;

class FileBag
{
	private ?array $files = null;
	
	private bool $multiple = false;
	
	private const FILE_KEYS = ['name', 'type', 'tmp_name', 'error', 'size'];
	
	public function __construct(array $files)
	{
		$this->normalize($files);
	}
	
	private function normalize(array $files): void
	{
		if (empty($files)) {
			return;
		}
		
		if (is_array($files[array_key_first($files)]['name'])) {
			$this->multiple = true;
			$this->refactor((array) reset($files));
		}
		
		foreach ($files as $name => $file) {
			if (empty($file)) {
				continue;
			}
			
			$this->setFile($file, $name);
		}
	}
	
	private function refactor(array $files): void
	{
		$count = count($files['name']);
		
		for ($i = 0; $i < $count; $i++) {
			foreach (self::FILE_KEYS as $key) {
				$result[$key] = $files[$key][$i];
			}

			$this->setFile($result ?? []);
			unset($result);
		}
	}
	
	private function setFile(array $file, ?string $name = null): void
	{
		unset($file['full_path']);
		
		if (self::FILE_KEYS === array_keys($file)
			&& UPLOAD_ERR_NO_FILE !== (int) $file['error']
			&& !is_array($file['error'])
		) {
			if ($this->isMultiple()) {
				$this->files[] = $file;
			} else {
				$this->files[$name] = $file;
			}
		}
	}
	
	public function isMultiple(): bool
	{
		return $this->multiple;
	}
	
	public function get(?string $name = null):? array
	{
		if (! $this->isMultiple() && count($this->files) === 1 && $name === null) {
			return $this->files[array_key_first($this->files)];
		}
		
		if ($name === null) {
			return $this->files;
		}
		
		return $this->files[$name];
	}
	
	public function all(): ?array
	{
		return $this->files;
	}
	
	public function hasFiles(): bool
	{
		return ! ($this->files === null);
	}
}
