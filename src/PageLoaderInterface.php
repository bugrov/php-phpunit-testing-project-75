<?php

namespace Hexlet\Code;

interface PageLoaderInterface
{
    public function save(?string $uploadDir = null): string;
}