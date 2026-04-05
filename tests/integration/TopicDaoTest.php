<?php
/**
 * TopicDao Integration Test
 * 
 * Tests the actual TopicDao class methods for code coverage
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class TopicDaoTest extends TestCase
{
    private static ?PDO $pdo = null;
    private ?TopicDao $topicDao = null;
    private ?Sanitize $sanitize = null;
    
    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware'
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            self::$pdo = null;
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }
    
    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('Test database not available');
            return;
        }
        
        $db = new Db();
        $db->setDbConnection([
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware'
        ]);
        
        Registry::set('dbc', $db);
        
        $this->topicDao = new TopicDao();
        $this->sanitize = new Sanitize();
        
        $this->cleanupTestTopics();
    }
    
    protected function tearDown(): void
    {
        $this->cleanupTestTopics();
        $this->topicDao = null;
        $this->sanitize = null;
    }
    
    private function cleanupTestTopics(): void
    {
        if (self::$pdo === null) return;
        
        try {
            self::$pdo->exec("DELETE FROM tbl_topics WHERE topic_title LIKE 'test_%'");
        } catch (PDOException $e) {
            // Ignore
        }
    }
    
    private function insertTestTopic(string $title, string $slug, string $status = 'Y', string $locale = 'en'): int
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_topics (topic_title, topic_slug, topic_status, topic_locale)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$title, $slug, $status, $locale]);
        return (int) self::$pdo->lastInsertId();
    }
    
    // ==================== findTopics ====================
    
    public function testFindTopics(): void
    {
        $this->insertTestTopic('test_topic_1', 'test-topic-1');
        $this->insertTestTopic('test_topic_2', 'test-topic-2');
        
        $topics = $this->topicDao->findTopics();
        
        $this->assertIsArray($topics);
        $this->assertNotEmpty($topics);
    }
    
    public function testFindTopicsWithOrderBy(): void
    {
        $this->insertTestTopic('test_order_topic', 'test-order-topic');
        
        $topics = $this->topicDao->findTopics('topic_title');
        
        $this->assertIsArray($topics);
    }
    
    public function testFindTopicsReturnsArray(): void
    {
        $this->insertTestTopic('test_array_topic', 'test-array-topic');
        
        $topics = $this->topicDao->findTopics();
        
        $this->assertIsArray($topics);
        $this->assertNotEmpty($topics);
        
        $topic = is_array($topics[0]) ? $topics[0] : (array) $topics[0];
        $this->assertArrayHasKey('ID', $topic);
        $this->assertArrayHasKey('topic_title', $topic);
        $this->assertArrayHasKey('topic_slug', $topic);
    }
    
    // ==================== findTopicById ====================
    
    public function testFindTopicById(): void
    {
        $topicId = $this->insertTestTopic('test_byid_topic', 'test-byid-topic');
        
        $topic = $this->topicDao->findTopicById($topicId, $this->sanitize);
        
        $this->assertNotNull($topic);
        $topicData = is_object($topic) ? (array) $topic : $topic;
        $this->assertEquals('test_byid_topic', $topicData['topic_title']);
    }
    
    public function testFindTopicByIdWithFetchMode(): void
    {
        $topicId = $this->insertTestTopic('test_fetch_topic', 'test-fetch-topic');
        
        $topic = $this->topicDao->findTopicById($topicId, $this->sanitize, PDO::FETCH_ASSOC);
        
        $this->assertIsArray($topic);
        $this->assertEquals('test_fetch_topic', $topic['topic_title']);
    }
    
    // ==================== findPostTopic ====================
    
    public function testFindPostTopic(): void
    {
        $topicId = $this->insertTestTopic('test_post_topic', 'test-post-topic');
        
        $postId = 1;
        
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_post_topic (post_id, topic_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$postId, $topicId]);
        
        $postTopic = $this->topicDao->findPostTopic($topicId, $postId);
        
        $this->assertNotNull($postTopic);
    }
    
    // ==================== createTopic ====================
    
    public function testCreateTopic(): void
    {
        $topicId = $this->topicDao->createTopic([
            'topic_title' => 'test_create_topic',
            'topic_slug' => 'test-create-topic',
            'topic_locale' => 'en'
        ]);
        
        $this->assertNotNull($topicId);
        $this->assertGreaterThan(0, (int)$topicId);
        
        $stmt = self::$pdo->prepare("SELECT ID FROM tbl_topics WHERE ID = ?");
        $stmt->execute([$topicId]);
        $this->assertNotEmpty($stmt->fetch());
    }
    
    public function testCreateTopicWithDefaultLocale(): void
    {
        $topicId = $this->topicDao->createTopic([
            'topic_title' => 'test_default_locale',
            'topic_slug' => 'test-default-locale'
        ]);
        
        $this->assertNotNull($topicId);
        
        $stmt = self::$pdo->prepare("SELECT topic_locale FROM tbl_topics WHERE ID = ?");
        $stmt->execute([$topicId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('en', $row['topic_locale']);
    }
    
    // ==================== updateTopic ====================
    
    public function testUpdateTopic(): void
    {
        $topicId = $this->insertTestTopic('test_update_topic', 'test-update-topic');
        
        $this->topicDao->updateTopic(
            $this->sanitize,
            [
                'topic_title' => 'test_updated_title',
                'topic_slug' => 'test-updated-slug',
                'topic_status' => 'N',
                'topic_locale' => 'en'
            ],
            $topicId
        );
        
        $stmt = self::$pdo->prepare("SELECT topic_title, topic_status FROM tbl_topics WHERE ID = ?");
        $stmt->execute([$topicId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('test_updated_title', $row['topic_title']);
        $this->assertEquals('N', $row['topic_status']);
    }
    
    // ==================== deleteTopic ====================
    
    public function testDeleteTopic(): void
    {
        $topicId = $this->insertTestTopic('test_delete_topic', 'test-delete-topic');
        
        $this->topicDao->deleteTopic($topicId, $this->sanitize);
        
        $stmt = self::$pdo->prepare("SELECT ID FROM tbl_topics WHERE ID = ?");
        $stmt->execute([$topicId]);
        $this->assertEmpty($stmt->fetch());
    }
    
    // ==================== checkTopicId ====================
    
    public function testCheckTopicId(): void
    {
        $topicId = $this->insertTestTopic('test_check_topic', 'test-check-topic');
        
        $exists = $this->topicDao->checkTopicId($topicId, $this->sanitize);
        
        $this->assertTrue($exists);
    }
    
    public function testCheckTopicIdNotFound(): void
    {
        $exists = $this->topicDao->checkTopicId(999999, $this->sanitize);
        
        $this->assertFalse($exists);
    }
    
    // ==================== totalTopicRecords ====================
    
    public function testTotalTopicRecords(): void
    {
        $this->insertTestTopic('test_total_1', 'test-total-1');
        $this->insertTestTopic('test_total_2', 'test-total-2');
        
        $total = $this->topicDao->totalTopicRecords();
        
        $this->assertIsInt($total);
        $this->assertGreaterThanOrEqual(2, $total);
    }
    
    // ==================== dropDownLocale ====================
    
    public function testDropDownLocale(): void
    {
        $html = $this->topicDao->dropDownLocale();
        
        $this->assertIsString($html);
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="topic_locale"', $html);
        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('Spanish', $html);
        $this->assertStringContainsString('French', $html);
    }
    
    public function testDropDownLocaleWithSelected(): void
    {
        $html = $this->topicDao->dropDownLocale('es');
        
        $this->assertIsString($html);
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('value="es"', $html);
    }
}
