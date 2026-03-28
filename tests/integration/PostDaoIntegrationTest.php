<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostDao Integration Test
 * 
 * Tests for post CRUD operations with database.
 * This file consolidates tests from PostIntegrationTest and PostDaoIntegrationTest.
 * 
 * @category Tests
 * @version 1.1
 */

use PHPUnit\Framework\TestCase;

class PostDaoIntegrationTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static ?int $postId = null;
    private static int $testAuthorId = 1;
    private static string $testSlug = '';
    
    private const TEST_TITLE = 'Test Post Title';
    private const TEST_CONTENT = 'This is test content for the post.';
    private const TEST_SUMMARY = 'This is a test post summary.';

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
        
        // Verify table exists
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_posts'")->fetchAll();
        if (empty($tables)) {
            self::markTestSkipped('tbl_posts table does not exist');
        }
        
        // Verify test author exists
        $stmt = self::$pdo->query("SELECT ID FROM tbl_users LIMIT 1");
        $user = $stmt->fetch();
        if ($user) {
            self::$testAuthorId = (int)$user['ID'];
        }
        
        // Clean up any existing test posts
        self::$pdo->exec("DELETE FROM tbl_posts WHERE post_slug LIKE 'test-post-title-%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$postId) {
            self::$pdo->exec("DELETE FROM tbl_posts WHERE ID = " . self::$postId);
        }
        
        if (self::$pdo) {
            self::$pdo = null;
        }
    }
    
    protected function setUp(): void
    {
        self::$postId = null;
        self::$testSlug = 'test-post-title-' . time();
    }

    public function testDatabaseConnection(): void
    {
        $this->assertNotNull(self::$pdo);
        $this->assertInstanceOf(PDO::class, self::$pdo);
    }
    
    public function testInsertPost(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_posts (
                post_author, post_date, post_modified, post_title, post_slug, 
                post_content, post_summary, post_status, post_visibility, 
                comment_status, post_type
            )
            VALUES (?, NOW(), NOW(), ?, ?, ?, ?, 'publish', 'public', 'open', 'blog')
        ");
        
        $result = $stmt->execute([
            self::$testAuthorId,
            self::TEST_TITLE,
            self::$testSlug,
            self::TEST_CONTENT,
            self::TEST_SUMMARY
        ]);
        
        $this->assertTrue($result);
        
        self::$postId = (int)self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$postId);
    }
    
    public function testSelectPostById(): void
    {
        if (!self::$postId) {
            $this->testInsertPost();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE ID = ?");
        $stmt->execute([self::$postId]);
        $post = $stmt->fetch();
        
        $this->assertIsArray($post);
        $this->assertEquals(self::TEST_TITLE, $post['post_title']);
        $this->assertEquals(self::$testSlug, $post['post_slug']);
        $this->assertEquals(self::TEST_CONTENT, $post['post_content']);
    }
    
    public function testSelectPostBySlug(): void
    {
        if (!self::$postId) {
            $this->testInsertPost();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE post_slug = ?");
        $stmt->execute([self::$testSlug]);
        $posts = $stmt->fetchAll();
        
        $this->assertIsArray($posts);
        $this->assertNotEmpty($posts);
        $this->assertEquals(self::$testSlug, $posts[0]['post_slug']);
    }
    
    public function testUpdatePost(): void
    {
        if (!self::$postId) {
            $this->testInsertPost();
        }
        
        $newTitle = 'Updated Test Post Title';
        $newContent = 'This is updated test post content.';
        
        $stmt = self::$pdo->prepare("
            UPDATE tbl_posts 
            SET post_title = ?, post_content = ?, post_modified = NOW() 
            WHERE ID = ?
        ");
        
        $result = $stmt->execute([$newTitle, $newContent, self::$postId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE ID = ?");
        $stmt->execute([self::$postId]);
        $post = $stmt->fetch();
        
        $this->assertEquals($newTitle, $post['post_title']);
        $this->assertEquals($newContent, $post['post_content']);
    }
    
    public function testDeletePost(): void
    {
        if (!self::$postId) {
            $this->testInsertPost();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_posts WHERE ID = ?");
        $result = $stmt->execute([self::$postId]);
        
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE ID = ?");
        $stmt->execute([self::$postId]);
        $post = $stmt->fetch();
        
        $this->assertFalse($post);
        self::$postId = null;
    }
    
    public function testSelectPublishedPosts(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE post_status = 'publish' AND post_type = 'blog'");
        $posts = $stmt->fetchAll();
        
        $this->assertIsArray($posts);
    }
    
    public function testSelectPostsByAuthor(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE post_author = ? AND post_type = 'blog'");
        $stmt->execute([self::$testAuthorId]);
        $posts = $stmt->fetchAll();
        
        $this->assertIsArray($posts);
    }
    
    public function testCountPosts(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_posts WHERE post_type = 'blog'");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
    
    public function testSearchPostsByTitle(): void
    {
        $searchTerm = 'Test';
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE post_title LIKE ? AND post_type = 'blog'");
        $stmt->execute(["%{$searchTerm}%"]);
        $posts = $stmt->fetchAll();
        
        $this->assertIsArray($posts);
    }
    
    public function testPostWithFulltextSearch(): void
    {
        // Skip if FULLTEXT index doesn't exist on test database
        $indexes = self::$pdo->query("SHOW INDEX FROM tbl_posts WHERE Index_type = 'FULLTEXT'")->fetchAll();
        if (empty($indexes)) {
            $this->markTestSkipped('FULLTEXT index not found on tbl_posts');
        }
        
        $searchTerm = 'content';
        $stmt = self::$pdo->prepare("
            SELECT * FROM tbl_posts 
            WHERE MATCH(post_title, post_content, post_tags) AGAINST(? IN BOOLEAN MODE)
            AND post_type = 'blog'
        ");
        $stmt->execute([$searchTerm]);
        $posts = $stmt->fetchAll();
        
        $this->assertIsArray($posts);
    }
    
    public function testSelectPostWithUserJoin(): void
    {
        $stmt = self::$pdo->prepare("
            SELECT p.*, u.user_login 
            FROM tbl_posts p
            INNER JOIN tbl_users u ON p.post_author = u.ID
            WHERE p.post_type = 'blog'
            LIMIT 1
        ");
        $stmt->execute();
        $post = $stmt->fetch();
        
        if ($post) {
            $this->assertArrayHasKey('user_login', $post);
        }
    }
}
