<?php
/**
 * Integration Tests for Comments
 * 
 * Tests database operations for comments table
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class CommentIntegrationTest extends TestCase
{
    private static $pdo;
    private static $postId;
    private static $commentId;
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
        
        // Create test post
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_posts (post_author, post_title, post_slug, post_content, post_status, post_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([self::$userId, 'Test Post', 'test-post', 'Content', 'publish', 'blog']);
        self::$postId = self::$pdo->lastInsertId();
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo) {
            if (self::$commentId) {
                self::$pdo->exec("DELETE FROM tbl_comments WHERE ID = " . self::$commentId);
            }
            if (self::$postId) {
                self::$pdo->exec("DELETE FROM tbl_posts WHERE ID = " . self::$postId);
            }
        }
    }
    
    public function testInsertComment(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_comments (comment_post_id, comment_author_name, comment_author_ip, comment_author_email, comment_content, comment_status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            self::$postId,
            'Test User',
            '127.0.0.1',
            'test@test.com',
            'Test comment content',
            'approved'
        ]);
        
        $this->assertTrue($result);
        
        self::$commentId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$commentId);
    }
    
    public function testSelectComment(): void
    {
        if (!self::$commentId) {
            $this->testInsertComment();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_comments WHERE ID = ?");
        $stmt->execute([self::$commentId]);
        $comment = $stmt->fetch();
        
        $this->assertIsArray($comment);
        $this->assertEquals('Test User', $comment['comment_author_name']);
    }
    
    public function testSelectCommentsByPost(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_comments WHERE comment_post_id = ? AND comment_status = 'approved'");
        $stmt->execute([self::$postId]);
        $comments = $stmt->fetchAll();
        
        $this->assertIsArray($comments);
    }
    
    public function testDeleteComment(): void
    {
        if (!self::$commentId) {
            $this->testInsertComment();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_comments WHERE ID = ?");
        $result = $stmt->execute([self::$commentId]);
        
        $this->assertTrue($result);
        self::$commentId = null;
    }
}
