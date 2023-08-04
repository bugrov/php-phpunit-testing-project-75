<?php

namespace Hexlet\Code;

class FileStorage implements FileStorageInterface
{
    public function save(string $uploadDir, string $fileName, mixed $data = null): bool
    {
        if (file_exists($uploadDir) === false) {
            mkdir($uploadDir, 0755, true);
        }

        return file_put_contents($uploadDir . DIRECTORY_SEPARATOR .$fileName, $data) !== false;
    }
}