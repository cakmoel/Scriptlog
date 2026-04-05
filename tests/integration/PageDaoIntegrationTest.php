<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PageDao Integration Test
 * 
 * Tests for page CRUD operations with database.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PageDaoIntegrationTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static ?int $pageId = null;
    private static int $testAuthorId = 1;
    
    private const TEST_TITLE = 'Test Page Title';
    private const TEST_SLUG = 'test-page-slug';
    private const TEST_CONTENT = 'This is test page content.';

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
        
        // Get test author
        $stmt = self::$pdo->query("SELECT ID FROM tbl_users LIMIT 1");
        $user = $stmt->fetch();
        if ($user) {
            self::$testAuthorId = (int)$user['ID'];
        }
        
        // Clean up existing test pages
        self::$pdo->exec("DELETE FROM tbl_posts WHERE post_slug LIKE 'test-page-%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$pageId) {
            self::$pdo->exec("DELETE FROM tbl_posts WHERE ID = " . self::$pageId);
        }
        
        if (self::$pdo) {
            self::$pdo = null;
        }
    }
    
    protected function setUp(): void
    {
        self::$pageId = null;
    }

    public function testInsertPage(): void
    {
        $slug = self::TEST_SLUG . '-' . time();
        
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_posts (post_author, post_date, post_modified, post_title, post_slug, post_content, post_status, post_type)
            VALUES (?, NOW(), NOW(), ?, ?, ?, 'publish', 'page')
        ");
        
        $result = $stmt->execute([
            self::$testAuthorId,
            self::TEST_TITLE,
            $slug,
            self::TEST_CONTENT
        ]);
        
        $this->assertTrue($result);
        
        self::$pageId = (int)self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$pageId);
    }
    
    public function testSelectPageById(): void
    {
        if (!self::$pageId) {
            $this->testInsertPage();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE ID = ? AND post_type = 'page'");
        $stmt->execute([self::$pageId]);
        $page = $stmt->fetch();
        
        $this->assertIsArray($page);
        $this->assertEquals(self::TEST_TITLE, $page['post_title']);
        $this->assertEquals('page', $page['post_type']);
    }
    
    public function testSelectPageBySlug(): void
    {
        if (!self::$pageId) {
            $this->testInsertPage();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE post_slug LIKE 'test-page-%' AND post_type = 'page'");
        $stmt->execute();
        $pages = $stmt->fetchAll();
        
        $this->assertIsArray($pages);
        $this->assertNotEmpty($pages);
    }
    
    public function testUpdatePage(): void
    {
        if (!self::$pageId) {
            $this->testInsertPage();
        }
        
        $newTitle = 'Updated Page Title';
        $newContent = 'Updated page content';
        
        $stmt = self::$pdo->prepare("UPDATE tbl_posts SET post_title = ?, post_content = ?, post_modified = NOW() WHERE ID = ?");
        $result = $stmt->execute([$newTitle, $newContent, self::$pageId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT post_title, post_content FROM tbl_posts WHERE ID = ?");
        $stmt->execute([self::$pageId]);
        $page = $stmt->fetch();
        
        $this->assertEquals($newTitle, $page['post_title']);
        $this->assertEquals($newContent, $page['post_content']);
    }
    
    public function testDeletePage(): void
    {
        if (!self::$pageId) {
            $this->testInsertPage();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_posts WHERE ID = ?");
        $result = $stmt->execute([self::$pageId]);
        
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE ID = ? AND post_type = 'page'");
        $stmt->execute([self::$pageId]);
        $page = $stmt->fetch();
        
        $this->assertFalse($page);
        self::$pageId = null;
    }
    
    public function testSelectAllPages(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_posts WHERE post_type = 'page'");
        $pages = $stmt->fetchAll();
        
        $this->assertIsArray($pages);
    }
    
    public function testCountPages(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_posts WHERE post_type = 'page'");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
}
