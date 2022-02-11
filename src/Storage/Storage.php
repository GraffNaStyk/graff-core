<?php

namespace App\Facades\Storage;

use App\Facades\Helpers\Str;
use App\Facades\Helpers\Dir;
use App\Models\File;

class Storage
{
    public const MIMES = [
        'txt' => 'text/plain',
        'css' => 'text/css',
        'json' => 'application/json',
        'xml' => 'application/xml',
        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        // archives
        'zip' => 'application/zip',
        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        // adobe
        'pdf' => 'application/pdf',
        // ms office
        'doc' => 'application/msword',
        'xls' => 'application/vnd.ms-excel',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
    ];
    
    private ?string $disk = null;
    
    public function disk(string $disk): Storage
    {
        Dir::create(storage_path($disk));
        $this->disk = storage_path($disk);
        
        return $this;
    }
    
    public function put(string $file, string $content, ?string $flags = null): Storage
    {
    	$file = ltrim($file, '/');
    	
    	if (file_put_contents($this->disk.'/'.$file, $content, $flags)) {
		    chmod($this->disk.'/'.$file, 0775);
	    } else {
    		throw new \DomainException('Cannot create file in path '.$this->disk.'/'.$file);
	    }
    	
    	return $this;
    }
	
	public function get(string $path): ?string
	{
		$filePath = $this->disk.'/'.ltrim($path, '/');
		
		if (is_readable($filePath)) {
			return file_get_contents($filePath);
		}

		return null;
	}
	
	public function mkDir(string $path, int $mode = 0775): Storage
	{
		$path = ltrim($path, '/');
		Dir::create($this->disk.'/'.$path, $mode);
		
		return $this;
	}
	
	private function checkFile(string $destination): bool
	{
		$pathInfo = pathinfo($destination);
		
		if (isset(self::MIMES[$pathInfo['extension']])
			&& self::MIMES[$pathInfo['extension']] === (string) mime_content_type($destination)
		) {
			return true;
		}
		
		$this->remove(str_replace(storage_path(), '', $destination));
		
		return false;
	}
	
	public function upload(array $file, string $destination = '/', ?string $as = null, ?\Closure $closure = null): bool
	{
		if ($file['error'] === UPLOAD_ERR_OK) {
			$this->mkDir($destination);
			
			$pathInfo  = pathinfo($file['name']);
			$location  = $this->disk.$destination;
			$location .= $as
				? mb_strtolower($as).'.'.$pathInfo['extension']
				: mb_strtolower($file['name']);
			
			
			if (move_uploaded_file($file['tmp_name'], $location) && $this->checkFile($location)) {
				chmod($location, 0775);
				
				if ($closure !== null) {
					$closure([
						'name' => $pathInfo['filename'],
						'dir'  => $this->disk.$destination,
						'ext'  => '.'.$pathInfo['extension'],
						'sha1' => sha1_file($location)
					]);
				}
	
				return true;
			}
			
			throw new \Exception('Cannot upload file '.$file['name']);
		}
		
		throw new \Exception('File has error '.$file['name']);
	}
	
	public function remove(string $path = null): bool
	{
		$path = ltrim($path, '/');
		
		if (is_dir($this->disk.'/'.$path)) {
			$elements = array_diff(scandir($this->disk.'/'.$path), ['.', '..']);
			
			foreach ($elements as $element) {
				self::remove($path.'/'.$element);
			}
			
			rmdir($this->disk.'/'.$path);
		}
		
		if (is_file($this->disk.'/'.$path)) {
			unlink($this->disk.'/'.$path);
		}
		
		return true;
	}
}
