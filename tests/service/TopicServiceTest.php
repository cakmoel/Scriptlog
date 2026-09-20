<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * TopicService Test
 * 
 * Tests for topic/category business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class TopicServiceTest extends TestCase
{
    private $topicService;
    private $topicDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->topicDaoMock = $this->createMock(\TopicDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->topicService = new \TopicService(
            $this->topicDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetTopicId(): void
    {
        $this->topicService->setTopicId(1);
        $this->assertTrue(true);
    }

    public function testSetTopicTitle(): void
    {
        $this->topicService->setTopicTitle('Test Topic');
        $this->assertTrue(true);
    }

    public function testSetTopicSlug(): void
    {
        $this->topicService->setTopicSlug('test-topic');
        $this->assertTrue(true);
    }

    public function testSetTopicStatus(): void
    {
        $this->topicService->setTopicStatus('Y');
        $this->assertTrue(true);
    }

    public function testTotalTopics(): void
    {
        $this->topicDaoMock->method('totalTopicRecords')->willReturn(5);
        $total = $this->topicService->totalTopics();
        $this->assertEquals(5, $total);
    }

    public function testGrabTopics(): void
    {
        $this->topicDaoMock->method('findTopics')->willReturn([]);
        $topics = $this->topicService->grabTopics();
        $this->assertIsArray($topics);
    }

    public function testGrabTopic(): void
    {
        $this->topicDaoMock->method('findTopicById')->willReturn(['ID' => 1, 'topic_title' => 'Test']);
        $topic = $this->topicService->grabTopic(1);
        $this->assertIsArray($topic);
    }

    public function testGetActiveTopicsApiWithDefaults(): void
    {
        $expected = [
            ['ID' => 1, 'topic_title' => 'Cat 1'],
            ['ID' => 2, 'topic_title' => 'Cat 2']
        ];

        $this->topicDaoMock->method('findActiveTopicsPaginated')
            ->with(10, 0, 'ID', 'DESC')
            ->willReturn($expected);

        $result = $this->topicService->getActiveTopicsApi();
        $this->assertSame($expected, $result);
    }

    public function testGetActiveTopicsApiWithPage2PerPage5(): void
    {
        $expected = [['ID' => 6, 'topic_title' => 'Cat 6']];

        $this->topicDaoMock->method('findActiveTopicsPaginated')
            ->with(5, 5, 'topic_title', 'ASC')
            ->willReturn($expected);

        $result = $this->topicService->getActiveTopicsApi(2, 5, 'topic_title', 'ASC');
        $this->assertSame($expected, $result);
    }

    public function testCountActiveTopicsApi(): void
    {
        $this->topicDaoMock->method('countActiveTopics')->willReturn(15);
        $total = $this->topicService->countActiveTopicsApi();
        $this->assertSame(15, $total);
    }

    public function testGetTopicApiReturnsTopic(): void
    {
        $expected = ['ID' => 1, 'topic_title' => 'Test', 'post_count' => 5];
        $this->topicDaoMock->method('findTopicWithPostCount')->with(1)->willReturn($expected);
        $result = $this->topicService->getTopicApi(1);
        $this->assertSame($expected, $result);
    }

    public function testGetTopicApiReturnsFalseWhenNotFound(): void
    {
        $this->topicDaoMock->method('findTopicWithPostCount')->with(999)->willReturn(false);
        $result = $this->topicService->getTopicApi(999);
        $this->assertFalse($result);
    }

    public function testGetPostsByTopicApiWithDefaults(): void
    {
        $expected = [['ID' => 1, 'post_title' => 'Post 1']];
        $this->topicDaoMock->method('findPostsByTopicPaginated')
            ->with(3, 10, 0, 'ID', 'DESC')
            ->willReturn($expected);

        $result = $this->topicService->getPostsByTopicApi(3);
        $this->assertSame($expected, $result);
    }

    public function testCountPostsByTopicApi(): void
    {
        $this->topicDaoMock->method('countPostsByTopic')->with(3)->willReturn(12);
        $total = $this->topicService->countPostsByTopicApi(3);
        $this->assertSame(12, $total);
    }
}
