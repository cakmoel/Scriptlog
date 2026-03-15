<?php
/**
 * Integration Tests for Media
 * 
 * Tests database operations for media table
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class MediaIntegrationTest extends TestCase
{
    private static $pdo;
    private static $mediaId;
    
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
        if (self::$pdo && self::$mediaId) {
            self::$pdo->exec("DELETE FROM tbl_media WHERE ID = " . self::$mediaId);
        }
    }
    
    public function testInsertMedia(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_media (media_filename, media_caption, media_type, media_target, media_user, media_access)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'test-image.jpg',
            'Test Image Caption',
            'image/jpeg',
            'blog',
            'admin',
            'public'
        ]);
        
        $this->assertTrue($result);
        
        self::$mediaId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$mediaId);
    }
    
    public function testSelectMedia(): void
    {
        if (!self::$mediaId) {
            $this->testInsertMedia();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_media WHERE ID = ?");
        $stmt->execute([self::$mediaId]);
        $media = $stmt->fetch();
        
        $this->assertIsArray($media);
        $this->assertEquals('test-image.jpg', $media['media_filename']);
    }
    
    public function testSelectAllMedia(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_media");
        $media = $stmt->fetchAll();
        
        $this->assertIsArray($media);
    }
    
    public function testDeleteMedia(): void
    {
        if (!self::$mediaId) {
            $this->testInsertMedia();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_media WHERE ID = ?");
        $result = $stmt->execute([self::$mediaId]);
        
        $this->assertTrue($result);
    }
}
