<?php

namespace Hexlet\Code;

use DiDom\Document;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class PageLoader implements PageLoaderInterface
{
    private ?string $url;
    private ?string $urlHost;
    private ?string $urlScheme;
    private ?string $urlPath;
    private mixed $client;
    private ?FileStorageInterface $fileStorage;
    private mixed $domDocument;
    private ?Logger $logger;

    /**
     * @throws ClientNullException
     * @throws BadUrlException
     */
    public function __construct(string $url, $client = null, ?FileStorageInterface $fileStorage = null, $domDocument = null)
    {
        if (!$client) {
            throw new ClientNullException('Client is required');
        }

        if (!$this->checkUrl($url)) {
            throw new BadUrlException('Bad url');
        }

        $this->url = $url;
        $this->parseUrlEntities();
        $this->client = $client;
        $this->fileStorage = $fileStorage ?? new FileStorage();
        $this->domDocument = $domDocument ?? new Document();
        $this->logger = new Logger(__CLASS__);
    }

    /**
     * @throws BadResponseException
     * @throws FileStorageException
     */
    public function save(?string $uploadDir = null): string
    {
        $uploadDir = $uploadDir ?? dirname(__FILE__, 2);

        $this->logger->pushHandler(new StreamHandler("$uploadDir/page-loader.log", Level::Debug));
        $this->logger->info('Start');

        $newHTMLFileName = $this->preparePathWithDelimiter("{$this->urlHost}{$this->urlPath}", true, '.html');

        $this->logger->info('Url and new filename', [
            'url' => $this->url,
            'filename' => $newHTMLFileName
        ]);

        $result = '';

        try {
            $result = $this->client->get($this->url)->getBody()->getContents();
        } catch (\Throwable $exception) {
            $this->logger->error('Get content from url error', [
                'url' => $this->url,
                'error' => $exception->getMessage()
            ]);
        }

        $this->logger->info('Result from given url', [
            'content' => $result
        ]);

        if (!$result) {
            return '';
        }

        $this->domDocument->loadHtml($result);
        $result = $this->domDocument->html();
        if (!$this->fileStorage->save($uploadDir, $newHTMLFileName, $result)) {
            $this->logger->error('Cannot save new file', [
                'uploadDir' => $uploadDir,
                'file' => $newHTMLFileName
            ]);
            throw new FileStorageException('Saving the file passed with an error');
        }

        $images = [];
        $links = [];
        $scripts = [];

        $this->domDocument->loadHtmlFile($uploadDir . DIRECTORY_SEPARATOR . $newHTMLFileName);

        if ($this->domDocument->has('img')) {
            $images = $this->domDocument->find('img');
        }
        if ($this->domDocument->has('link')) {
            $links = $this->domDocument->find('link');
        }
        if ($this->domDocument->has('script')) {
            $scripts = $this->domDocument->find('script');
        }

        $this->logger->info('Data from html', [
            'images' => [
                'count' => count($images),
                'content' => $images
            ],
            'links' => [
                'count' => count($links),
                'content' => $links
            ],
            'scripts' => [
                'count' => count($scripts),
                'content' => $scripts
            ]
        ]);

        $newHTMLFileDir = $this->preparePathWithDelimiter("{$this->urlHost}{$this->urlPath}", true, '_files');

        $this->logger->info('New file directory', [
            'name' => $newHTMLFileDir,
            'realPath' => $uploadDir . DIRECTORY_SEPARATOR . $newHTMLFileDir
        ]);

        if (!$this->fileStorage->saveDir($uploadDir . DIRECTORY_SEPARATOR . $newHTMLFileDir)) {
            $this->logger->error('Cannot save directory for files', [
                'uploadDir' => $uploadDir,
                'dir' => $newHTMLFileDir
            ]);
            throw new FileStorageException('Cannot create directory for files: ' . $uploadDir . DIRECTORY_SEPARATOR . $newHTMLFileDir);
        }

        $this->prepareTags($images, 'src', $uploadDir, $newHTMLFileDir);
        $this->prepareTags($links, 'href', $uploadDir, $newHTMLFileDir, 'html');
        $this->prepareTags($scripts, 'src', $uploadDir, $newHTMLFileDir);

        $this->fileStorage->save($uploadDir, $newHTMLFileName, $this->domDocument->html());

        $this->logger->info('Finish');

        return $uploadDir . DIRECTORY_SEPARATOR . $newHTMLFileName;
    }

    public function prepareTags(
        array $entity,
        string $attribute,
        string $uploadDir,
        string $newHTMLFileDir,
        string $defaultExt = ''
    ): void
    {
        foreach ($entity as $item) {
            $tagSrc = $item->getAttribute($attribute);
            $this->logger->info("Tag src: $tagSrc");
            if (!$tagSrc || str_starts_with($tagSrc, 'data:image')) continue;

            $downloadSrc = $this->prepareSrcUrlToDownload($tagSrc);
            $this->logger->info("Download src: $downloadSrc");
            if (!$downloadSrc) continue;

            $tagSrc = str_replace(
                ($this->urlScheme ? $this->urlScheme . '://' : '') . $this->urlHost,
                '',
                $tagSrc
            );

            $ext = pathinfo($downloadSrc, PATHINFO_EXTENSION) ?: $defaultExt;
            if ($ext) {
                $tagSrc = str_replace(".$ext", '', $tagSrc);
            }

            $tagSrc = $this->preparePathWithDelimiter($tagSrc, false, ".$ext");
            $pathToSave = "$uploadDir/$newHTMLFileDir/$tagSrc";

            $this->logger->info('Prepared tag', [
                'src' => $tagSrc,
                'ext' => $ext,
                'downloadSrc' => $downloadSrc,
                'savePath' => $pathToSave
            ]);

            try {
                $saveFileResult = $this->client->request(
                    'GET',
                    $downloadSrc,
                    ['sink' => $pathToSave]
                );
            } catch (\Throwable $exception) {
                $this->logger->error('Get source from attribute error', [
                    'src' => $downloadSrc,
                    'savePath' => $pathToSave,
                    'error' => $exception->getMessage()
                ]);
            }

            $item->setAttribute($attribute, "$newHTMLFileDir/$tagSrc");
        }
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

    public function parseUrlEntities(): static
    {
        $urlEntity = parse_url($this->url);
        $this->urlHost = $urlEntity['host'];
        $this->urlScheme = $urlEntity['scheme'] ?? '';
        $this->urlPath = $urlEntity['path'] ?? '';

        return $this;
    }

    public function preparePathWithDelimiter(string $value, bool $isDir = false, ?string $additionalString = ''): string
    {
        return preg_replace(
            "/\W+/m",
            "-",
            ltrim((!$isDir ? $this->urlHost.'/' : '') . $value, '/')
        ) . $additionalString;
    }

    public function prepareSrcUrlToDownload(string $url): string
    {
        $urlEntity = parse_url($url);
        $urlHost = $urlEntity['host'] ?? '';

        if ($urlHost && $urlHost !== $this->urlHost) {
            return '';
        }

        $urlPath = $urlEntity['path'] ? ltrim($urlEntity['path'], '/') : '';

        return "$this->urlScheme://$this->urlHost/$urlPath";
    }
}