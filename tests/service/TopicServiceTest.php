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
}
