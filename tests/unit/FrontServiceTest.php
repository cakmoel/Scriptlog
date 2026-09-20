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

    public function testGetSimplePostReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getSimplePost(9999));
    }

    public function testGetSimpleTopicReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getSimpleTopic(9999));
    }

    public function testGetSimplePageReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getSimplePage(9999));
    }

    public function testGetSimpleArchiveReturnsEmptyArrayWithoutDb(): void
    {
        $service = new FrontService();
        $result = $service->getSimpleArchive();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSearchTagReturnsEmptyArrayWithoutDb(): void
    {
        $service = new FrontService();
        $result = $service->searchTag('nonexistent');
        $this->assertIsArray($result);
    }

    public function testSearchTagReturnsEmptyArrayForEmptyTag(): void
    {
        $service = new FrontService();
        $result = $service->searchTag('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetTagListsReturnsEmptyArrayWithoutDb(): void
    {
        $service = new FrontService();
        $result = $service->getTagLists();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetArchivePostsReturnsNullForEmptyValues(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getArchivePosts([]));
    }

    public function testGetArchivePostsReturnsNullForMissingMonth(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getArchivePosts(['year' => '2025']));
    }

    public function testGetGalleriesReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getGalleries(0, 5));
    }

    public function testGetPublishedTopicFallsBackToDaoWithSlugMatch(): void
    {
        $topicDao = $this->createMock('TopicDao');
        $topicDao->method('findTopics')
            ->willReturn([
                ['ID' => 1, 'topic_title' => 'Test', 'topic_slug' => 'test-slug', 'topic_status' => 'Y']
            ]);

        $service = new FrontService(null, null, $topicDao);
        $result = $service->getPublishedTopic('test-slug');

        $this->assertNotNull($result);
        $this->assertEquals('test-slug', $result['topic_slug']);
    }

    public function testGetPublishedTopicFallsBackToDaoNoSlugMatch(): void
    {
        $topicDao = $this->createMock('TopicDao');
        $topicDao->method('findTopics')
            ->willReturn([
                ['ID' => 1, 'topic_title' => 'Test', 'topic_slug' => 'test-slug', 'topic_status' => 'Y']
            ]);

        $service = new FrontService(null, null, $topicDao);
        $result = $service->getPublishedTopic('other-slug');

        $this->assertNull($result);
    }

    public function testGetPublishedTopicByIdFallsBackToDao(): void
    {
        $topicDao = $this->createMock('TopicDao');
        $topicDao->method('findTopicById')
            ->willReturn(['ID' => 5, 'topic_title' => 'Found', 'topic_slug' => 'found-slug']);

        $service = new FrontService(null, null, $topicDao);
        $result = $service->getPublishedTopicById(5);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result['ID']);
    }

    public function testGetPublishedPostReturnsFromDaoWhenAvailable(): void
    {
        $postDao = $this->createMock('PostDao');
        $postDao->method('findPost')
            ->willReturn(['ID' => 42, 'post_title' => 'Test Post']);

        $service = new FrontService($postDao);
        $result = $service->getPublishedPost(42);

        $this->assertNotNull($result);
        $this->assertEquals(42, $result['ID']);
    }

    public function testConstructorDoesNotThrowWhenRegistryAbsent(): void
    {
        $service = new FrontService();
        $this->assertInstanceOf(FrontService::class, $service);
    }
}
