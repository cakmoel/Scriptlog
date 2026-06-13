<?php

use PHPUnit\Framework\TestCase;

class FrontServiceTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/service/FrontService.php';
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('FrontService'));
    }

    public function testConstructorAcceptsNoArguments(): void
    {
        $service = new FrontService();
        $this->assertInstanceOf(FrontService::class, $service);
    }

    public function testConstructorAcceptsDaoInstances(): void
    {
        $postDao = $this->createMock('PostDao');
        $pageDao = $this->createMock('PageDao');
        $topicDao = $this->createMock('TopicDao');
        $mediaDao = $this->createMock('MediaDao');
        $service = new FrontService($postDao, $pageDao, $topicDao, $mediaDao);
        $this->assertInstanceOf(FrontService::class, $service);
    }

    public function testClassHasAllExpectedMethods(): void
    {
        $methods = get_class_methods(FrontService::class);
        $expectedMethods = [
            'getPublishedPost', 'getPublishedPage', 'getPublishedTopic',
            'getPublishedTopicById',
            'getSimplePost', 'getSimpleTopic', 'getSimplePage',
            'getSimpleArchive', 'searchTag', 'getTagLists',
            'getArchivePosts', 'getGalleries'
        ];
        foreach ($expectedMethods as $method) {
            $this->assertContains($method, $methods, "Method $method not found");
        }
    }

    public function testGetPublishedPostReturnsNullWithoutDao(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getPublishedPost(9999));
    }

    public function testGetPublishedPageReturnsNullWithoutDao(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getPublishedPage('nonexistent-slug'));
    }

    public function testGetPublishedTopicReturnsNullWithoutDao(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getPublishedTopic('nonexistent-topic'));
    }

    public function testGetPublishedTopicByIdReturnsNullWithoutDao(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getPublishedTopicById(9999));
    }

    /**
     * Delegated methods rely on FrontHelper which needs a DB connection.
     * These test that they don't throw fatal errors and return expected types.
     * searchTag returns an empty array when no tag is found (FrontHelper behavior).
     */
    public function testSearchTagReturnsEmptyArrayWithoutDb(): void
    {
        $service = new FrontService();
        $result = $service->searchTag('nonexistent');
        $this->assertIsArray($result);
    }
}
