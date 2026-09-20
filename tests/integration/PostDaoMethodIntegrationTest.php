<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostDao Method Integration Test
 * 
 * Tests PostDao class methods with actual database operations.
 * This tests the actual DAO layer, not raw SQL.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostDaoMethodIntegrationTest extends TestCase
{
    private static $dbc;
    private static $postDao;
    private static $postId;
    private static $testAuthorId = 1;
    private static $sanitize;
    
    private const TEST_TITLE = 'Test Post Title';
    private const TEST_SLUG = 'test-post-title';
    private const TEST_CONTENT = 'This is test content for the post.';
    private const TEST_SUMMARY = 'This is a test post summary.';
    private const TEST_TAGS = 'test,phpunit,integration';

    public static function setUpBeforeClass(): void
    {
        // Initialize custom Db connection
        $dsn = 'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4';
        $username = 'blogwareuser';
        $password = 'userblogware';
        
        self::$dbc = new Db(
            [$dsn, $username, $password],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Check if table exists
        $tables = self::$dbc->dbQuery("SHOW TABLES LIKE 'tbl_posts'")->fetchAll();
        if (empty($tables)) {
            self::markTestSkipped('tbl_posts table does not exist');
        }
        
        // Get test author
        $stmt = self::$dbc->dbQuery("SELECT ID FROM tbl_users LIMIT 1");
        $user = $stmt->fetch();
        if ($user) {
            self::$testAuthorId = (int)$user['ID'];
        }
        
        // Register database connection in Registry
        if (class_exists('Registry')) {
            Registry::set('dbc', self::$dbc);
        }
        
        // Create Sanitize instance for DAO
        if (class_exists('Sanitize')) {
            self::$sanitize = new Sanitize();
        }
        
        // Clean up any existing test posts
        self::$dbc->dbQuery("DELETE FROM tbl_posts WHERE post_slug LIKE 'test-post-title-%' OR post_title LIKE 'Test Post%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$postId) {
            self::$dbc->dbQuery("DELETE FROM tbl_posts WHERE ID = " . self::$postId);
            self::$dbc->dbQuery("DELETE FROM tbl_post_topic WHERE post_id = " . self::$postId);
        }
        
        if (self::$dbc) {
            self::$dbc = null;
        }
    }
    
    protected function setUp(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        
        if (!Registry::isKeySet('dbc')) {
            Registry::set('dbc', self::$dbc);
        }
    }

    public function testPostDaoCanBeInstantiated(): void
    {
        $postDao = new PostDao();
        $this->assertInstanceOf(PostDao::class, $postDao);
    }
    
    public function testCreatePostViaDao(): void
    {
        $postDao = new PostDao();
        
        $bind = [
            'post_author' => self::$testAuthorId,
            'post_date' => date('Y-m-d H:i:s'),
            'post_title' => self::TEST_TITLE . ' ' . time(),
            'post_slug' => self::TEST_SLUG . '-' . time(),
            'post_content' => self::TEST_CONTENT,
            'post_summary' => self::TEST_SUMMARY,
            'post_status' => 'publish',
            'post_visibility' => 'public',
            'post_password' => '',
            'post_tags' => self::TEST_TAGS,
            'post_headlines' => 0,
            'comment_status' => 'open',
            'passphrase' => ''
        ];
        
        // Test with a topic (create a topic first)
        $topicId = $this->createTestTopic();
        
        $postId = $postDao->createPost($bind, $topicId);
        
        $this->assertGreaterThan(0, $postId);
        self::$postId = $postId;
        
        // Verify post was created
        $post = self::$dbc->dbQuery("SELECT * FROM tbl_posts WHERE ID = ?", [$postId])->fetch();
        
        $this->assertIsArray($post);
        $this->assertEquals($bind['post_title'], $post['post_title']);
        $this->assertEquals($bind['post_content'], $post['post_content']);
    }
    
    public function testFindPostByIdViaDao(): void
    {
        if (!self::$postId) {
            $this->testCreatePostViaDao();
        }
        
        $postDao = new PostDao();
        
        $post = $postDao->findPost(self::$postId, self::$sanitize, null, false);
        
        $this->assertIsArray($post);
        $this->assertEquals(self::$postId, $post['ID']);
    }
    
    public function testGetPostByIdViaDao(): void
    {
        if (!self::$postId) {
            $this->testCreatePostViaDao();
        }
        
        $postDao = new PostDao();
        
        $post = $postDao->getPostById(self::$postId);
        
        $this->assertIsArray($post);
        $this->assertEquals(self::$postId, $post['ID']);
        $this->assertEquals('blog', $post['post_type']);
    }
    
    public function testGetPostByIdReturnsNullForMissingPost(): void
    {
        $postDao = new PostDao();
        
        $post = $postDao->getPostById(99999999);
        
        $this->assertNull($post);
    }
    
    public function testSetPostTopicsViaDao(): void
    {
        if (!self::$postId) {
            $this->testCreatePostViaDao();
        }
        
        $postDao = new PostDao();
        
        $topicA = $this->createTestTopic();
        $topicB = $this->createTestTopic();
        
        // Assign two topics
        $postDao->setPostTopics(self::$postId, [$topicA, $topicB]);
        
        $topics = $postDao->findTopicsByPostId(self::$postId);
        $topicIds = array_column($topics, 'ID');
        sort($topicIds);
        
        $this->assertCount(2, $topicIds);
        $this->assertSame([$topicA, $topicB], array_values($topicIds));
        
        // Replacing with a single topic must remove the previous relationships
        $postDao->setPostTopics(self::$postId, [$topicA]);
        
        $topics = $postDao->findTopicsByPostId(self::$postId);
        $topicIds = array_column($topics, 'ID');
        
        $this->assertCount(1, $topicIds);
        $this->assertContains($topicA, $topicIds);
        $this->assertNotContains($topicB, $topicIds);
    }
    
    private function createTestTopic(): int
    {
        $slug = 'test-topic-' . time();
        
        self::$dbc->dbQuery("
            INSERT INTO tbl_topics (topic_title, topic_slug, topic_status)
            VALUES ('Test Topic', ?, 'Y')
        ", [$slug]);
        
        return (int)self::$dbc->dbLastInsertId();
    }
    
    public function testFindPostsViaDao(): void
    {
        $postDao = new PostDao();
        
        $posts = $postDao->findPosts('ID', null, false);
        
        $this->assertIsArray($posts);
    }
    
    public function testTotalPostRecordsViaDao(): void
    {
        $postDao = new PostDao();
        
        $total = $postDao->totalPostRecords();
        
        $this->assertIsInt($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }
    
    public function testTotalPostRecordsByAuthorViaDao(): void
    {
        $postDao = new PostDao();

        $total = $postDao->totalPostRecords(self::$testAuthorId);

        $this->assertIsInt($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }
    
    public function testCheckPostIdViaDao(): void
    {
        if (!self::$postId) {
            $this->testCreatePostViaDao();
        }
        
        $postDao = new PostDao();
        
        $exists = $postDao->checkPostId(self::$postId, self::$sanitize);
        
        $this->assertTrue($exists);
    }
    
    public function testCheckPostIdNonExistentViaDao(): void
    {
        $postDao = new PostDao();
        
        $exists = $postDao->checkPostId(999999999, self::$sanitize);
        
        $this->assertFalse($exists);
    }
    
    public function testUpdatePostViaDao(): void
    {
        if (!self::$postId) {
            $this->testCreatePostViaDao();
        }

        $postDao = new PostDao();

        $bind = [
            'post_author' => self::$testAuthorId,
            'post_modified' => date('Y-m-d H:i:s'),
            'post_title' => self::TEST_TITLE . ' updated',
            'post_slug' => self::TEST_SLUG . '-' . time() . '-updated',
            'post_content' => 'Updated content.',
            'post_summary' => 'Updated summary.',
            'post_status' => 'publish',
            'post_visibility' => 'public',
            'post_tags' => 'updated',
            'post_headlines' => 0,
            'post_locale' => 'en',
            'comment_status' => 'open',
            'post_password' => '',
            'passphrase' => ''
        ];

        $postDao->updatePost(self::$sanitize, $bind, self::$postId, 1);

        // Verify the update was committed
        $post = self::$dbc->dbQuery("SELECT * FROM tbl_posts WHERE ID = ?", [self::$postId])->fetch();

        $this->assertIsArray($post);
        $this->assertEquals($bind['post_title'], $post['post_title']);
    }
    
    public function testFindAdjacentPostViaDao(): void
    {
        if (!self::$postId) {
            $this->testCreatePostViaDao();
        }

        $postDao = new PostDao();

        $previous = $postDao->findAdjacentPost(self::$postId, 'previous');
        $next = $postDao->findAdjacentPost(self::$postId, 'next');

        $this->assertTrue(
            $previous === null || (is_array($previous) && isset($previous['ID'], $previous['post_title'], $previous['post_slug'])),
            'previous row should be null or carry ID/post_title/post_slug'
        );
        $this->assertTrue(
            $next === null || (is_array($next) && isset($next['ID'], $next['post_title'], $next['post_slug'])),
            'next row should be null or carry ID/post_title/post_slug'
        );
    }

    public function testFindActiveTopicsByPostIdViaDao(): void
    {
        if (!self::$postId) {
            $this->testCreatePostViaDao();
        }

        $postDao = new PostDao();

        $activeTopic = $this->createTestTopic();
        $inactiveTopic = $this->createTestTopic();

        // Mark the second topic inactive
        self::$dbc->dbQuery("UPDATE tbl_topics SET topic_status = 'N' WHERE ID = ?", [$inactiveTopic]);

        $postDao->setPostTopics(self::$postId, [$activeTopic, $inactiveTopic]);

        $topics = $postDao->findActiveTopicsByPostId(self::$postId);
        $topicIds = array_column($topics, 'ID');

        $this->assertContains($activeTopic, $topicIds);
        $this->assertNotContains($inactiveTopic, $topicIds);
    }

    public function testDeletePostViaDao(): void
    {
        // Create a new post to delete
        $postDao = new PostDao();
        
        $bind = [
            'post_author' => self::$testAuthorId,
            'post_date' => date('Y-m-d H:i:s'),
            'post_title' => 'Post to Delete',
            'post_slug' => 'post-to-delete-' . time(),
            'post_content' => 'Content to be deleted.',
            'post_summary' => 'Summary to be deleted.',
            'post_status' => 'draft',
            'post_visibility' => 'public',
            'post_password' => '',
            'post_tags' => '',
            'post_headlines' => 0,
            'comment_status' => 'closed',
            'passphrase' => ''
        ];
        
        $tempPostId = $postDao->createPost($bind, 1);
        
        // Delete the post
        $postDao->deletePost($tempPostId, self::$sanitize);
        
        // Verify deletion
        $post = self::$dbc->dbQuery("SELECT * FROM tbl_posts WHERE ID = ?", [$tempPostId])->fetch();
        
        $this->assertFalse($post);
    }
    
    public function testAnonymizePostAuthor(): void
    {
        // Create a post with test author
        $slug = 'author-test-post-' . time();
        self::$dbc->dbQuery("
            INSERT INTO tbl_posts (post_author, post_date, post_modified, post_title, post_slug, post_content, post_status, post_type)
            VALUES (?, NOW(), NOW(), ?, ?, ?, 'publish', 'blog')
        ", [
            self::$testAuthorId,
            'Author Test Post',
            $slug,
            'Testing author anonymization'
        ]);
        
        $testPostId = self::$dbc->dbLastInsertId();
        
        // Test anonymization
        $postDao = new PostDao();
        $result = $postDao->anonymizePostAuthor(self::$testAuthorId);
        
        $this->assertTrue($result);
        
        // Verify author was changed to anonymous (ID 1)
        $post = self::$dbc->dbQuery("SELECT post_author FROM tbl_posts WHERE ID = ?", [$testPostId])->fetch();
        
        $this->assertEquals(1, $post['post_author']);
        
        // Clean up
        self::$dbc->dbQuery("DELETE FROM tbl_posts WHERE ID = " . $testPostId);
    }
}
