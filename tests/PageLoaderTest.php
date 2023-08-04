<?php

use Hexlet\Code\BadUrlException;
use Hexlet\Code\ClientNullException;
use Hexlet\Code\FakeClient;
use Hexlet\Code\FileStorage;
use Hexlet\Code\PageLoader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class PageLoaderTest extends TestCase
{
    private mixed $client;
    private mixed $root;
    private string $url = 'https://ru.hexlet.io/courses';
    private string $expectedPath = 'ru-hexlet-io-courses.html';

    public function setUp(): void
    {
        $this->client = $this->createMock(FakeClient::class);
        $this->root = vfsStream::setup('test', 0777);
    }

    public function testPageLoaderThrowsExceptionWithEmptyClient()
    {
        $this->expectException(ClientNullException::class);
        $pageLoader = new PageLoader($this->url);
    }

    public function testPageLoaderBadUrlProvided()
    {
        $this->expectException(BadUrlException::class);
        $pageLoader = new PageLoader('some url', $this->client);
    }

    public function testSaveSuccess()
    {
        $expectedFile = file_get_contents($this->getFixtureFullPath($this->expectedPath));

        $this->client->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getBody')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getContents')
            ->willReturn($expectedFile);

        $this->assertFalse($this->root->hasChild("hexlet/$this->expectedPath"));

        $pageLoader = new PageLoader($this->url, $this->client);
        $result = $pageLoader->save($this->root->url() . '/hexlet');

        $this->assertEquals(0755, $this->root->getChild('hexlet')->getPermissions());
        $this->assertTrue($this->root->hasChild("hexlet/$this->expectedPath"));
        $this->assertEquals($expectedFile, $this->root->getChild("hexlet/$this->expectedPath")->getContent());
        $this->assertEquals(vfsStream::url("test/hexlet/{$this->expectedPath}"), $result);
    }

    public function testSaveWithNoReturnResultFromClient()
    {
        $expected = '';

        $this->client->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getBody')
            ->willReturnSelf();
        $this->client->expects($this->once())
            ->method('getContents')
            ->willReturn($expected);

        $this->assertFalse($this->root->hasChild("hexlet/$this->expectedPath"));

        $pageLoader = new PageLoader($this->url, $this->client);
        $result = $pageLoader->save($this->root->url() . '/hexlet');

        $this->assertFalse($this->root->hasChild("hexlet/$this->expectedPath"));
        $this->assertEquals($expected, $result);
    }

    public function testSaveWithNoSaveFile()
    {
        $expected = '';
        $expectedFile = file_get_contents($this->getFixtureFullPath($this->expectedPath));
        $fileStorage = $this->createMock(FileStorage::class);

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
            ->with(
                'badDirectory',
                $this->expectedPath,
                $expectedFile
            )
            ->willReturn(false);

        $pageLoader = new PageLoader($this->url, $this->client);
        $result = $pageLoader->save('badDirectory', $fileStorage);

        $this->assertEquals($expected, $result);
    }

    public function getFixtureFullPath($fixtureName)
    {
        $parts = [__DIR__, 'fixtures', $fixtureName];
        return realpath(implode('/', $parts));
    }
}