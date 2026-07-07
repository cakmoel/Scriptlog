<?php

use PHPUnit\Framework\TestCase;

class FrontServiceTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/service/FrontService.php';
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

    public function testGetPublishedPostFallsBackToPostDao(): void
    {
        $postDao = $this->createMock('PostDao');
        $postDao->method('findPost')
            ->willReturn(['ID' => 1, 'post_title' => 'Test']);
        $service = new FrontService($postDao);
        $result = $service->getPublishedPost(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['ID']);
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

    public function testGetPublishedTopicFallsBackToTopicDao(): void
    {
        $topicDao = $this->createMock('TopicDao');
        $topicDao->method('findTopics')
            ->willReturn([
                ['ID' => 1, 'topic_title' => 'PHP', 'topic_slug' => 'php'],
                ['ID' => 2, 'topic_title' => 'JS', 'topic_slug' => 'js']
            ]);
        $service = new FrontService(null, null, $topicDao);
        $result = $service->getPublishedTopic('php');
        $this->assertIsArray($result);
        $this->assertEquals('php', $result['topic_slug']);
    }

    public function testGetPublishedTopicByIdFallsBackToTopicDao(): void
    {
        $topicDao = $this->createMock('TopicDao');
        $topicDao->method('findTopicById')
            ->willReturn(['ID' => 1, 'topic_title' => 'PHP', 'topic_slug' => 'php']);
        $service = new FrontService(null, null, $topicDao);
        $result = $service->getPublishedTopicById(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['ID']);
    }

    public function testGetPublishedTopicByIdReturnsNullWhenTopicDaoReturnsEmpty(): void
    {
        $topicDao = $this->createMock('TopicDao');
        $topicDao->method('findTopicById')
            ->willReturn([]);
        $service = new FrontService(null, null, $topicDao);
        $this->assertNull($service->getPublishedTopicById(9999));
    }

    public function testGetSimplePostReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getSimplePost(1));
    }

    public function testGetSimpleTopicReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getSimpleTopic(1));
    }

    public function testGetSimplePageReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getSimplePage(1));
    }

    public function testGetSimpleArchiveReturnsEmptyArrayWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertSame([], $service->getSimpleArchive());
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
        $this->assertSame([], $service->getTagLists());
    }

    public function testGetArchivePostsReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getArchivePosts(['month' => '03', 'year' => '2025']));
    }

    public function testGetArchivePostsReturnsNullForMissingMonth(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getArchivePosts(['year' => '2025']));
    }

    public function testGetArchivePostsReturnsNullForMissingYear(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getArchivePosts(['month' => '03']));
    }

    public function testGetArchivePostsReturnsNullForEmptyValues(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getArchivePosts([]));
    }

    public function testGetGalleriesReturnsNullWithoutDb(): void
    {
        $service = new FrontService();
        $this->assertNull($service->getGalleries(0, 10));
    }
}
