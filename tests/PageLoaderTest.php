<?php

use DiDom\Document;
use Hexlet\Code\BadResponseException;
use Hexlet\Code\BadUrlException;
use Hexlet\Code\ClientNullException;
use Hexlet\Code\FakeClient;
use Hexlet\Code\FileStorage;
use Hexlet\Code\FileStorageException;
use Hexlet\Code\PageLoader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use function Downloader\Downloader\downloadPage;

class PageLoaderTest extends TestCase
{
    private mixed $client;
    private mixed $root;
    private string $url = 'https://www.eberhart.ru/delivery';
    private string $expectedPath = 'www-eberhart-ru-delivery.html';
    private string $expectedFilesPath = 'www-eberhart-ru-delivery_files';

    public function setUp(): void
    {
        $this->client = $this->createMock(FakeClient::class);
        $this->root = vfsStream::setup('test', 0777);
        $this->root->chown(vfsStream::OWNER_ROOT);
    }

    public function testPageLoaderThrowsExceptionWithEmptyClient()
    {
        $this->expectException(ClientNullException::class);
        downloadPage($this->url);
    }

    public function testPageLoaderBadUrlProvided()
    {
        $this->expectException(BadUrlException::class);
        downloadPage('some url', null, $this->client);
    }

    public function testSaveSuccess()
    {
        $sourceFile = file_get_contents($this->getFixtureFullPath('www-eberhart-ru-delivery-before.html'));
        $expectedFile = file_get_contents($this->getFixtureFullPath($this->expectedPath));

        $this->client->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getBody')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getContents')
            ->willReturn($sourceFile);
        $this->client->expects($this->any())
            ->method('request')
            ->willReturnSelf();

        $this->assertFalse($this->root->hasChild("hexlet/$this->expectedPath"));
        $this->assertFalse($this->root->hasChild("hexlet/$this->expectedFilesPath"));

        // create log file
        $this->root->addChild(vfsStream::newFile('hexlet/page-loader.log', 0755));

        $result = downloadPage($this->url, $this->root->url() . '/hexlet', $this->client);

        $this->assertEquals(0755, $this->root->getChild('hexlet')->getPermissions());
        $this->assertEquals(0755, $this->root->getChild("hexlet/$this->expectedFilesPath")->getPermissions());

        $this->assertEquals($expectedFile, $this->root->getChild("hexlet/$this->expectedPath")->getContent());

        $this->assertStringContainsString('Start', $this->root->getChild('hexlet/page-loader.log')->getContent());
        $this->assertStringContainsString('Url and new filename', $this->root->getChild('hexlet/page-loader.log')->getContent());
        $this->assertStringContainsString('Result from given url', $this->root->getChild('hexlet/page-loader.log')->getContent());
        $this->assertStringContainsString('Data from html', $this->root->getChild('hexlet/page-loader.log')->getContent());
        $this->assertStringContainsString('New file directory', $this->root->getChild('hexlet/page-loader.log')->getContent());
        $this->assertStringContainsString('Finish', $this->root->getChild('hexlet/page-loader.log')->getContent());

        $this->assertEquals(vfsStream::url("test/hexlet/$this->expectedPath"), $result);
    }

    public function testSaveWithNoReturnResultFromClient()
    {
//        $this->expectException(BadResponseException::class);

        $this->client->expects($this->once())
            ->method('get')
            ->with($this->url)
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getBody')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getContents')
            ->willThrowException(new \Exception('Bad response'));

        // create log file
        $this->root->addChild(vfsStream::newFile('hexlet/page-loader.log', 0755));

        $result = downloadPage($this->url, vfsStream::url('test/hexlet'), $this->client);

        $this->assertStringContainsString('Get content from url error', $this->root->getChild('hexlet/page-loader.log')->getContent());
        $this->assertEmpty($result);
    }

    public function testSaveWithNoSaveFile()
    {
        $expectedFile = file_get_contents($this->getFixtureFullPath($this->expectedPath));
        $fileStorage = $this->createMock(FileStorage::class);

        $this->expectException(FileStorageException::class);

        $this->client->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getBody')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getContents')
            ->willReturn($expectedFile);

        $fileStorage->expects($this->once())
            ->method('save')
            ->willReturn(false);

        // create log file
        $this->root->addChild(vfsStream::newFile('hexlet/page-loader.log', 0755));

        $result = downloadPage($this->url, 'badDirectory', $this->client, $fileStorage);

        $this->assertStringContainsString('Cannot save new file', $this->root->getChild('hexlet/page-loader.log')->getContent());
    }

    public function testSaveWithNoSaveFileDirectory()
    {
        $expectedFile = file_get_contents($this->getFixtureFullPath($this->expectedPath));
        $fileStorage = $this->createMock(FileStorage::class);
        $domDocument = $this->createMock(Document::class);

        $this->expectException(FileStorageException::class);

        $this->client->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getBody')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getContents')
            ->willReturn($expectedFile);

        $fileStorage->expects($this->once())
            ->method('save')
            ->willReturn(true);
        $fileStorage->expects($this->once())
            ->method('saveDir')
            ->willReturn(false);

        // create log file
        $this->root->addChild(vfsStream::newFile('hexlet/page-loader.log', 0755));

        $result = downloadPage($this->url, $this->root->url() . '/hexlet', $this->client, $fileStorage, $domDocument);
    }

    public function getFixtureFullPath($fixtureName)
    {
        $parts = [__DIR__, 'fixtures', $fixtureName];
        return realpath(implode('/', $parts));
    }
}