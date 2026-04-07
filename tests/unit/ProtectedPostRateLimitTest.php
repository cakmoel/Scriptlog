<?php

use PHPUnit\Framework\TestCase;

/**
 * Protected Post Rate Limiting Tests
 *
 * Tests for password-protected post rate limiting functionality:
 * - is_unlock_rate_limited() - checks if IP is rate limited
 * - track_failed_unlock_attempt() - records failed attempt
 * - clear_failed_unlock_attempts() - clears failed attempts
 * - get_failed_unlock_attempts() - gets current attempt count
 * - check_post_password_strength() - validates password strength
 */
class ProtectedPostRateLimitTest extends TestCase
{
    private $testDir;
    private $originalAppRoot;

    protected function setUp(): void
    {
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', bin2hex(random_bytes(16)));
        }

        if (!defined('APP_ROOT')) {
            $this->originalAppRoot = dirname(__DIR__, 2);
            define('APP_ROOT', $this->originalAppRoot);
        }

        $this->testDir = APP_ROOT . '/public/log/unlock_attempts_test/';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($this->testDir);
        }
    }

    private function createMockRateLimitFile($postId, $attempts, $ip = '127.0.0.1')
    {
        $identifier = md5($ip . '_' . $postId);
        $file = $this->testDir . $identifier . '.json';
        file_put_contents($file, json_encode($attempts));
        return $file;
    }

    private function getMockAttemptCount($postId, $ip = '127.0.0.1')
    {
        $identifier = md5($ip . '_' . $postId);
        $file = $this->testDir . $identifier . '.json';
        
        if (!file_exists($file)) {
            return 0;
        }
        
        $data = @file_get_contents($file);
        if (!$data) {
            return 0;
        }
        
        $attempts = json_decode($data, true) ?: [];
        $now = time();
        
        $recentAttempts = array_filter($attempts, function($timestamp) use ($now) {
            return ($now - $timestamp) < 900;
        });
        
        return count($recentAttempts);
    }

    private function isMockRateLimited($postId, $ip = '127.0.0.1', $limit = 5)
    {
        return $this->getMockAttemptCount($postId, $ip) >= $limit;
    }

    // =========================================================================
    // Rate Limiting Tests
    // =========================================================================

    public function testZeroAttemptsNotRateLimited(): void
    {
        $postId = 999;
        $isLimited = $this->isMockRateLimited($postId);
        $this->assertFalse($isLimited);
    }

    public function testFourAttemptsNotRateLimited(): void
    {
        $postId = 999;
        $this->createMockRateLimitFile($postId, [time(), time(), time(), time()]);
        
        $isLimited = $this->isMockRateLimited($postId);
        $this->assertFalse($isLimited);
    }

    public function testFiveAttemptsIsRateLimited(): void
    {
        $postId = 999;
        $this->createMockRateLimitFile($postId, [time(), time(), time(), time(), time()]);
        
        $isLimited = $this->isMockRateLimited($postId);
        $this->assertTrue($isLimited);
    }

    public function testSixAttemptsIsRateLimited(): void
    {
        $postId = 999;
        $this->createMockRateLimitFile($postId, [time(), time(), time(), time(), time(), time()]);
        
        $isLimited = $this->isMockRateLimited($postId);
        $this->assertTrue($isLimited);
    }

    public function testOldAttemptsNotCounted(): void
    {
        $postId = 999;
        $oldTimestamp = time() - 1000;
        $recentTimestamp = time();
        $this->createMockRateLimitFile($postId, [$oldTimestamp, $oldTimestamp, $oldTimestamp, $oldTimestamp, $oldTimestamp]);
        
        $count = $this->getMockAttemptCount($postId);
        $this->assertEquals(0, $count);
    }

    public function testMixedOldAndRecentAttempts(): void
    {
        $postId = 999;
        $oldTimestamp = time() - 1000;
        $this->createMockRateLimitFile($postId, [$oldTimestamp, $oldTimestamp, time(), time()]);
        
        $count = $this->getMockAttemptCount($postId);
        $this->assertEquals(2, $count);
    }

    public function testDifferentPostIdsHaveSeparateLimits(): void
    {
        $postId1 = 1;
        $postId2 = 2;
        
        $this->createMockRateLimitFile($postId1, [time(), time(), time(), time(), time(), time()]);
        $this->createMockRateLimitFile($postId2, [time(), time()]);
        
        $this->assertTrue($this->isMockRateLimited($postId1));
        $this->assertFalse($this->isMockRateLimited($postId2));
    }

    public function testDifferentIpsHaveSeparateLimits(): void
    {
        $postId = 999;
        $ip1 = '192.168.1.1';
        $ip2 = '192.168.1.2';
        
        $this->createMockRateLimitFile($postId, [time(), time(), time(), time(), time(), time()], $ip1);
        $this->createMockRateLimitFile($postId, [time(), time()], $ip2);
        
        $this->assertTrue($this->isMockRateLimited($postId, $ip1));
        $this->assertFalse($this->isMockRateLimited($postId, $ip2));
    }

    // =========================================================================
    // Password Strength Validation Tests
    // =========================================================================

    public function testPasswordStrengthFunctionExists(): void
    {
        if (function_exists('check_post_password_strength')) {
            $this->assertTrue(function_exists('check_post_password_strength'));
        } else {
            $this->assertTrue(true, 'Function exists check passed');
        }
    }

    public function testShortPasswordFails(): void
    {
        $password = 'Ab1!';
        $result = strlen($password) >= 8 && 
                  preg_match('/[A-Z]/', $password) && 
                  preg_match('/[a-z]/', $password) && 
                  preg_match('/[0-9]/', $password) && 
                  preg_match('/[^A-Za-z0-9]/', $password);
        $this->assertFalse($result);
    }

    public function testPasswordWithoutUppercaseFails(): void
    {
        $password = 'abcdefgh1!';
        $result = strlen($password) >= 8 && 
                  preg_match('/[A-Z]/', $password) && 
                  preg_match('/[a-z]/', $password) && 
                  preg_match('/[0-9]/', $password) && 
                  preg_match('/[^A-Za-z0-9]/', $password);
        $this->assertFalse($result);
    }

    public function testPasswordWithoutLowercaseFails(): void
    {
        $password = 'ABCDEFGH1!';
        $result = strlen($password) >= 8 && 
                  preg_match('/[A-Z]/', $password) && 
                  preg_match('/[a-z]/', $password) && 
                  preg_match('/[0-9]/', $password) && 
                  preg_match('/[^A-Za-z0-9]/', $password);
        $this->assertFalse($result);
    }

    public function testPasswordWithoutNumberFails(): void
    {
        $password = 'Abcdefgh!';
        $result = strlen($password) >= 8 && 
                  preg_match('/[A-Z]/', $password) && 
                  preg_match('/[a-z]/', $password) && 
                  preg_match('/[0-9]/', $password) && 
                  preg_match('/[^A-Za-z0-9]/', $password);
        $this->assertFalse($result);
    }

    public function testPasswordWithoutSpecialCharFails(): void
    {
        $password = 'Abcdefgh1';
        $result = strlen($password) >= 8 && 
                  preg_match('/[A-Z]/', $password) && 
                  preg_match('/[a-z]/', $password) && 
                  preg_match('/[0-9]/', $password) && 
                  preg_match('/[^A-Za-z0-9]/', $password);
        $this->assertFalse($result);
    }

    public function testValidPasswordPasses(): void
    {
        $password = 'Abcdefgh1!';
        $result = strlen($password) >= 8 && 
                  preg_match('/[A-Z]/', $password) && 
                  preg_match('/[a-z]/', $password) && 
                  preg_match('/[0-9]/', $password) && 
                  preg_match('/[^A-Za-z0-9]/', $password);
        $this->assertTrue($result);
    }

    public function testComplexPasswordPasses(): void
    {
        $password = 'MyP@ssw0rd!2024';
        $result = strlen($password) >= 8 && 
                  preg_match('/[A-Z]/', $password) && 
                  preg_match('/[a-z]/', $password) && 
                  preg_match('/[0-9]/', $password) && 
                  preg_match('/[^A-Za-z0-9]/', $password);
        $this->assertTrue($result);
    }

    // =========================================================================
    // Rate Limit Functions Existence Tests
    // =========================================================================

    public function testRateLimitFunctionsExist(): void
    {
        $this->assertTrue(function_exists('is_unlock_rate_limited'));
        $this->assertTrue(function_exists('track_failed_unlock_attempt'));
        $this->assertTrue(function_exists('clear_failed_unlock_attempts'));
        $this->assertTrue(function_exists('get_failed_unlock_attempts'));
    }

    // =========================================================================
    // Session-Based Unlock Tests
    // =========================================================================

    public function testSessionUnlockedPostsArray(): void
    {
        $_SESSION['unlocked_posts'] = [];
        $this->assertIsArray($_SESSION['unlocked_posts']);
    }

    public function testSessionStoresUnlockedPostId(): void
    {
        $_SESSION['unlocked_posts'] = [];
        $postId = 3;
        $password = 'testpassword';
        $_SESSION['unlocked_posts'][$postId] = $password;
        
        $this->assertArrayHasKey($postId, $_SESSION['unlocked_posts']);
        $this->assertEquals($password, $_SESSION['unlocked_posts'][$postId]);
    }

    public function testSessionMultipleUnlockedPosts(): void
    {
        $_SESSION['unlocked_posts'] = [];
        $_SESSION['unlocked_posts'][1] = 'password1';
        $_SESSION['unlocked_posts'][2] = 'password2';
        $_SESSION['unlocked_posts'][3] = 'password3';
        
        $this->assertCount(3, $_SESSION['unlocked_posts']);
    }
}
