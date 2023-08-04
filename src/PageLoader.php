<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientInterface;

class PageLoader implements PageLoaderInterface
{
    private ?string $url;
    private mixed $client;

    /**
     * @throws ClientNullException
     * @throws BadUrlException
     */
    public function __construct(string $url, $client = null)
    {
        if (!$client) {
            throw new ClientNullException('Client is required');
        }

        if (!$this->checkUrl($url)) {
            throw new BadUrlException('Bad url');
        }

        $this->url = $url;
        $this->client = $client;
    }

    public function save(?string $uploadDir = null, ?FileStorageInterface $fileStorage = null): string
    {
        $fileStorage = $fileStorage ?? new FileStorage();

        $uploadDir = $uploadDir ?? dirname(__FILE__, 2);

        $result = $this->client->get($this->url)->getBody()->getContents();

        if (!$result) {
            return '';
        }

        $newFileName = $this->parseUrlToHtmlUrl();

        if ($fileStorage->save($uploadDir, $newFileName, $result)) {
            return $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
        }

        return '';
    }

    public function checkUrl(string $url): bool
    {
        $regex = "((https?|ftp)\:\/\/)?"; // SCHEME
        $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
        $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
        $regex .= "(\:[0-9]{2,5})?"; // Port
        $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
        $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
        $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

        return (bool)preg_match("/^$regex$/i", $url);
    }

    public function parseUrlToHtmlUrl(): string
    {
        $urlEntity = parse_url($this->url);
        $host = $urlEntity['host'];
        $path = $urlEntity['path'] ?? '';
        $newUrl = "{$host}{$path}";

        return preg_replace("/\W+/m", "-", $newUrl) . '.html';
    }
}