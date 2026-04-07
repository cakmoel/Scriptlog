<?php
/**
 * Integration Tests for DAOs
 * 
 * Tests database operations with test database
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DaoIntegrationTest extends TestCase
{
    private static $pdo;
    private static $testUserId;
    
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
        
        self::$pdo->exec("DELETE FROM tbl_users WHERE user_email = 'test@example.com'");
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM tbl_users WHERE user_email = 'test@example.com'");
        }
    }
    
    public function testDatabaseConnection(): void
    {
        $this->assertNotNull(self::$pdo);
        $this->assertInstanceOf(PDO::class, self::$pdo);
    }
    
    public function testInsertUser(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_session)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            'author',
            ''
        ]);
        
        $this->assertTrue($result);
        
        self::$testUserId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$testUserId);
    }
    
    public function testSelectUser(): void
    {
        if (!self::$testUserId) {
            $this->testInsertUser();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_users WHERE ID = ?");
        $stmt->execute([self::$testUserId]);
        $user = $stmt->fetch();
        
        $this->assertIsArray($user);
        $this->assertEquals('testuser', $user['user_login']);
        $this->assertEquals('test@example.com', $user['user_email']);
    }
    
    public function testUpdateUser(): void
    {
        if (!self::$testUserId) {
            $this->testInsertUser();
        }
        
        $stmt = self::$pdo->prepare("
            UPDATE tbl_users SET user_fullname = ? WHERE ID = ?
        ");
        
        $result = $stmt->execute(['Test User Updated', self::$testUserId]);
        
        $this->assertTrue($result);
        
        $stmt = self::$pdo->prepare("SELECT user_fullname FROM tbl_users WHERE ID = ?");
        $stmt->execute([self::$testUserId]);
        $user = $stmt->fetch();
        
        $this->assertEquals('Test User Updated', $user['user_fullname']);
    }
    
    public function testDeleteUser(): void
    {
        if (!self::$testUserId) {
            $this->testInsertUser();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_users WHERE ID = ?");
        $result = $stmt->execute([self::$testUserId]);
        
        $this->assertTrue($result);
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_users WHERE ID = ?");
        $stmt->execute([self::$testUserId]);
        $user = $stmt->fetch();
        
        $this->assertFalse($user);
    }
    
    public function testUserCount(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as cnt FROM tbl_users");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['cnt']);
    }
}
