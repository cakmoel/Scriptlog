<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * UserDao Integration Test
 * 
 * Tests for user CRUD operations with database.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class UserDaoIntegrationTest extends TestCase
{
    private static $pdo;
    private static $userId;
    private static $testEmail = 'test-user@example.com';
    private static $testLogin = 'testuser';
    private static $testPassword = 'password123';
    private static $testFullname = 'Test User';

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
        
        // Check if table exists
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_users'")->fetchAll();
        if (empty($tables)) {
            self::markTestSkipped('tbl_users table does not exist');
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$userId) {
            self::$pdo->exec("DELETE FROM tbl_users WHERE ID = " . self::$userId);
        }
        
        if (self::$pdo) {
            self::$pdo = null;
        }
    }
    
    public function testInsertUser(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_fullname, user_registered, user_session)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $hashedPassword = password_hash(self::$testPassword, PASSWORD_DEFAULT);
        $userSession = md5(uniqid(rand(), true)); // Generate a session-like value
        
        $result = $stmt->execute([
            self::$testLogin,
            self::$testEmail,
            $hashedPassword,
            'administrator',
            self::$testFullname,
            $userSession
        ]);
        
        $this->assertTrue($result);
        
        self::$userId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$userId);
    }
    
    public function testSelectUserById(): void
    {
        if (!self::$userId) {
            $this->testInsertUser();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_users WHERE ID = ?");
        $stmt->execute([self::$userId]);
        $user = $stmt->fetch();
        
        $this->assertIsArray($user);
        $this->assertEquals(self::$testLogin, $user['user_login']);
        $this->assertEquals(self::$testEmail, $user['user_email']);
        $this->assertEquals(self::$testFullname, $user['user_fullname']);
    }
    
    public function testSelectUserByEmail(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_users WHERE user_email = ?");
        $stmt->execute([self::$testEmail]);
        $users = $stmt->fetchAll();
        
        $this->assertIsArray($users);
        $this->assertNotEmpty($users);
        $this->assertEquals(self::$testEmail, $users[0]['user_email']);
    }
    
    public function testSelectUserByLogin(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_users WHERE user_login = ?");
        $stmt->execute([self::$testLogin]);
        $users = $stmt->fetchAll();
        
        $this->assertIsArray($users);
        $this->assertNotEmpty($users);
        $this->assertEquals(self::$testLogin, $users[0]['user_login']);
    }
    
    public function testUpdateUser(): void
    {
        if (!self::$userId) {
            $this->testInsertUser();
        }
        
        $newFullname = 'Updated Test User';
        $stmt = self::$pdo->prepare("
            UPDATE tbl_users 
            SET user_fullname = ? 
            WHERE ID = ?
        ");
        
        $result = $stmt->execute([$newFullname, self::$userId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_users WHERE ID = ?");
        $stmt->execute([self::$userId]);
        $user = $stmt->fetch();
        
        $this->assertEquals($newFullname, $user['user_fullname']);
    }
    
    public function testDeleteUser(): void
    {
        if (!self::$userId) {
            $this->testInsertUser();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_users WHERE ID = ?");
        $result = $stmt->execute([self::$userId]);
        
        $this->assertTrue($result);
        self::$userId = null;
    }
    
    public function testCountUsers(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_users");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
    
    public function testSelectUsersByLevel(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_users WHERE user_level = ?");
        $stmt->execute(['administrator']);
        $users = $stmt->fetchAll();
        
        $this->assertIsArray($users);
    }
    
    public function testUserExistsByEmail(): void
    {
        // Insert a user first to test existence
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_fullname, user_registered, user_session)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $testEmail = 'exists-test@example.com';
        $hashedPassword = password_hash('testpass123', PASSWORD_DEFAULT);
        $userSession = md5(uniqid(rand(), true));
        
        $stmt->execute([
            'exists_test_user',
            $testEmail,
            $hashedPassword,
            'author',
            'Exists Test User',
            $userSession
        ]);
        
        $insertedId = self::$pdo->lastInsertId();
        
        // Now test if the user exists
        $stmt = self::$pdo->prepare("SELECT COUNT(*) as count FROM tbl_users WHERE user_email = ?");
        $stmt->execute([$testEmail]);
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['count']);
        
        // Clean up
        $stmt = self::$pdo->prepare("DELETE FROM tbl_users WHERE ID = ?");
        $stmt->execute([$insertedId]);
    }
    
    public function testUserExistsByLogin(): void
    {
        // Insert a user first to test existence
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_fullname, user_registered, user_session)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $testLogin = 'login-test-user';
        $hashedPassword = password_hash('testpass123', PASSWORD_DEFAULT);
        $userSession = md5(uniqid(rand(), true));
        
        $stmt->execute([
            $testLogin,
            'login-test@example.com',
            $hashedPassword,
            'author',
            'Login Test User',
            $userSession
        ]);
        
        $insertedId = self::$pdo->lastInsertId();
        
        // Now test if the user exists
        $stmt = self::$pdo->prepare("SELECT COUNT(*) as count FROM tbl_users WHERE user_login = ?");
        $stmt->execute([$testLogin]);
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['count']);
        
        // Clean up
        $stmt = self::$pdo->prepare("DELETE FROM tbl_users WHERE ID = ?");
        $stmt->execute([$insertedId]);
    }
}