<?php

namespace App\Facades\Storage;

use App\Facades\Helpers\Str;

class FileProvider
{
    private string $disk;

    public function setDisk(string $path): void
    {
        $this->disk = rtrim($path, '/').'/';
    }

    private function checkFile(string $destination): bool
    {
        $pathInfo = pathinfo($destination);

        if (isset(Storage::MIMES[$pathInfo['extension']])
            && Storage::MIMES[$pathInfo['extension']] === (string) mime_content_type($destination)
        ) {
            return true;
        }

        $this->remove($destination);

        return false;
    }

    public function upload(array $file, ?string $as = null, ?\Closure $closure = null): bool
    {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $hash      = Str::hash(70);
            $pathInfo  = pathinfo($file['name']);
            $location  = $this->disk;
            $location .= $as
                ? mb_strtolower($as).'.'.$pathInfo['extension']
                : $hash.'.'.$pathInfo['extension'];


            if (move_uploaded_file($file['tmp_name'], $location) && $this->checkFile($location)) {
                if ($closure !== null) {
                    $closure([
                        'name' => $pathInfo['filename'],
                        'dir'  => $this->disk,
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

    public function remove(string $file = null): bool
    {
        if ($file === null || $file === '') {
            return false;
        }

        if (is_file($file)) {
            unlink($file);
            return true;
        }

        $file = ltrim($file, '/');

        if (is_file($this->disk.$file)) {
            unlink($this->disk.$file);
        }

        return true;
    }
}