<?php
/**
 * Integration Tests for Topics
 * 
 * Tests database operations for topics table
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class TopicIntegrationTest extends TestCase
{
    private static $pdo;
    private static $topicId;
    
    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$topicId) {
            self::$pdo->exec("DELETE FROM tbl_topics WHERE ID = " . self::$topicId);
        }
    }
    
    public function testInsertTopic(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_topics (topic_title, topic_slug, topic_status)
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'Test Topic',
            'test-topic',
            'Y'
        ]);
        
        $this->assertTrue($result);
        
        self::$topicId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$topicId);
    }
    
    public function testSelectTopic(): void
    {
        if (!self::$topicId) {
            $this->testInsertTopic();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_topics WHERE ID = ?");
        $stmt->execute([self::$topicId]);
        $topic = $stmt->fetch();
        
        $this->assertIsArray($topic);
        $this->assertEquals('Test Topic', $topic['topic_title']);
    }
    
    public function testSelectAllTopics(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_topics WHERE topic_status = 'Y'");
        $topics = $stmt->fetchAll();
        
        $this->assertIsArray($topics);
    }
    
    public function testDeleteTopic(): void
    {
        if (!self::$topicId) {
            $this->testInsertTopic();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_topics WHERE ID = ?");
        $result = $stmt->execute([self::$topicId]);
        
        $this->assertTrue($result);
    }
}
