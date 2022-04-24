<?php

namespace App\Facades\Storage;

use App\Facades\Config\Config;
use App\Facades\Helpers\Dir;
use App\Facades\Helpers\Str;
use App\Facades\Http\Response;

class Storage
{
    public const MIMES = [
        'txt'  => 'text/plain',
        'css'  => 'text/css',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        // images
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'svg'  => 'image/svg+xml',
        // archives
        'zip' => 'application/zip',
        // audio/video
        'mp3' => 'audio/mpeg',
        'qt'  => 'video/quicktime',
        'mov' => 'video/quicktime',
        // adobe
        'pdf' => 'application/pdf',
        // ms office
        'doc'  => 'application/msword',
        'xls'  => 'application/vnd.ms-excel',
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

    public function path(string $path): string
    {
    	return $this->disk.$path;
    }

	public function display(string $path): ?string
	{
		$path = str_replace('/storage', null, $path);
		$path = array_filter(explode('/', $path));
		$name = end($path);
		array_pop($path);

		$disk = storage_path(implode('/', $path));

		if (is_readable($disk.'/'.$name) && is_file($disk.'/'.$name)) {
			return (new Response())->file($disk.'/'.$name)->getResponse();
		} else if (Config::get('app.no_photo_assets_img') !== null) {
			return (new Response())->file(assets_path(Config::get('app.no_photo_assets_img')))->getResponse();
		}

		return null;
	}

    public function put(string $file, string $content, ?int $flags = null): Storage
    {
    	$file = ltrim($file, '/');
    	$mask = umask(0);

    	if (file_put_contents($this->disk.'/'.$file, $content, $flags)) {
		    chmod($this->disk.'/'.$file, 0775);
		    umask($mask);
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

			$hash      = Str::hash(40);
			$pathInfo  = pathinfo($file['name']);
			$location  = $this->disk.rtrim($destination, '/').'/';
			$location .= $as
				? mb_strtolower($as).'.'.$pathInfo['extension']
				: $hash.'.'.$pathInfo['extension'];
			$mask      = umask(0);

			if (move_uploaded_file($file['tmp_name'], $location) && $this->checkFile($location)) {
				chmod($location, 0775);
				umask($mask);

				if ($closure !== null) {
					$closure([
						'name' => $pathInfo['filename'],
						'dir'  => str_replace(storage_path(), '/', $this->disk).rtrim($destination, '/'),
						'ext'  => '.'.$pathInfo['extension'],
						'sha1' => sha1_file($location),
						'hash' => $hash
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
		if ($path === null || $path === '') {
			return false;
		}
		
		if (str_starts_with($path, '/public')) {
			$path = str_replace('/public', null, $path);
		}

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

	public static function create(): Storage
	{
		return new self();
	}
}
