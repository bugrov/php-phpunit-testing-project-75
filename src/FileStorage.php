<?php

namespace Hexlet\Code;

class FileStorage implements FileStorageInterface
{
    public function save(string $uploadDir, string $fileName, mixed $data = null): bool
    {
        if ($this->isFileExists($uploadDir) === false) {
            mkdir($uploadDir, 0755, true);
        }

        return file_put_contents($uploadDir . DIRECTORY_SEPARATOR .$fileName, $data) !== false;
    }

    public function saveDir(string $dir): bool
    {
        if ($this->isFileExists($dir) === false) {
            return mkdir($dir, 0755, true);
        }

        return true;
    }

    public function isFileExists(string $path): bool
    {
        return file_exists($path);
    }
}