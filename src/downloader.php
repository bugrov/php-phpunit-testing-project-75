<?php
declare(strict_types=1);

namespace Downloader\Downloader;

use Hexlet\Code\FileStorageInterface;
use Hexlet\Code\PageLoader;

function downloadPage(
    string                $url,
    ?string               $uploadDir = null,
                          $client = null,
    ?FileStorageInterface $fileStorage = null,
                          $domDocument = null
): string
{
    $pageLoader = new PageLoader($url, $client, $fileStorage, $domDocument);
    return $pageLoader->save($uploadDir);
}