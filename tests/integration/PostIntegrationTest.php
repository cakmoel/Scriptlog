<?php
/**
 * Integration Tests for Posts
 * 
 * Tests database operations for posts table
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostIntegrationTest extends TestCase
{
    private static $pdo;
    private static $postId;
    private static $userId;
    
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
        
        // Get admin user ID
        $stmt = self::$pdo->query("SELECT ID FROM tbl_users LIMIT 1");
        $user = $stmt->fetch();
        self::$userId = $user['ID'];
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$postId) {
            self::$pdo->exec("DELETE FROM tbl_posts WHERE ID = " . self::$postId);
        }
    }
    
    public function testInsertPost(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_posts (post_author, post_title, post_slug, post_content, post_status, post_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            self::$userId,
            'Test Post Title',
            'test-post-title',
            'This is test content for the post.',
            'publish',
            'blog'
        ]);
        
        $this->assertTrue($result);
        
        self::$postId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$postId);
    }
    
    public function testSelectPost(): void
    {
        if (!self::$postId) {
            $this->testInsertPost();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE ID = ?");
        $stmt->execute([self::$postId]);
        $post = $stmt->fetch();
        
        $this->assertIsArray($post);
        $this->assertEquals('Test Post Title', $post['post_title']);
        $this->assertEquals('test-post-title', $post['post_slug']);
    }
    
    public function testUpdatePost(): void
    {
        if (!self::$postId) {
            $this->testInsertPost();
        }
        
        $stmt = self::$pdo->prepare("
            UPDATE tbl_posts SET post_title = ?, post_modified = NOW() WHERE ID = ?
        ");
        
        $result = $stmt->execute(['Updated Post Title', self::$postId]);
        
        $this->assertTrue($result);
        
        $stmt = self::$pdo->prepare("SELECT post_title FROM tbl_posts WHERE ID = ?");
        $stmt->execute([self::$postId]);
        $post = $stmt->fetch();
        
        $this->assertEquals('Updated Post Title', $post['post_title']);
    }
    
    public function testDeletePost(): void
    {
        if (!self::$postId) {
            $this->testInsertPost();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_posts WHERE ID = ?");
        $result = $stmt->execute([self::$postId]);
        
        $this->assertTrue($result);
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_posts WHERE ID = ?");
        $stmt->execute([self::$postId]);
        $post = $stmt->fetch();
        
        $this->assertFalse($post);
    }
    
    public function testSelectPublishedPosts(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_posts WHERE post_status = 'publish'");
        $posts = $stmt->fetchAll();
        
        $this->assertIsArray($posts);
    }
}
