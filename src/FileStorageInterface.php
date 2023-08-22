<?php

namespace Hexlet\Code;

interface FileStorageInterface
{
    public function save(string $uploadDir, string $fileName, mixed $data = null): bool;

    public function saveDir(string $dir): bool;

    public function isFileExists(string $path): bool;
}