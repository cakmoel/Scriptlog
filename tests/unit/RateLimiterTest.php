<?php

use PHPUnit\Framework\TestCase;

/**
 * RateLimiterTest
 *
 * Tests for the RateLimiter class
 *
 * @category Unit Test
 * @author Blogware Team
 * @license MIT
 */
class RateLimiterTest extends TestCase
{
    /**
     * @var string
     */
    private $testCacheDir;

    /**
     * @var RateLimiter
     */
    private $limiter;

    protected function setUp(): void
    {
        $this->testCacheDir = sys_get_temp_dir() . DS . 'ratelimit_test_' . uniqid();
        if (!is_dir($this->testCacheDir)) {
            mkdir($this->testCacheDir, 0755, true);
        }
        $this->limiter = new RateLimiter($this->testCacheDir);
    }

    protected function tearDown(): void
    {
        // Clean up test cache directory
        if (is_dir($this->testCacheDir)) {
            $files = glob($this->testCacheDir . DS . '*.ratelimit');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            @rmdir($this->testCacheDir);
        }
    }

    public function testCheckAllowsRequestsUnderLimit()
    {
        $result = $this->limiter->check('test_user', 10, 60);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(9, $result['remaining']);
        $this->assertEquals(0, $result['retry_after']);
    }

    public function testCheckBlocksRequestsOverLimit()
    {
        $limit = 3;

        // Make requests up to the limit
        for ($i = 0; $i < $limit; $i++) {
            $result = $this->limiter->check('test_user', $limit, 60);
            $this->assertTrue($result['allowed']);
        }

        // Next request should be blocked
        $result = $this->limiter->check('test_user', $limit, 60);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertGreaterThan(0, $result['retry_after']);
    }

    public function testCheckReturnsCorrectRemainingCount()
    {
        $this->limiter->check('test_user', 5, 60);
        $result = $this->limiter->check('test_user', 5, 60);

        $this->assertEquals(3, $result['remaining']);
    }

    public function testCheckUsesDifferentKeysIndependently()
    {
        // Exhaust limit for user_a
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('user_a', 5, 60);
        }

        // user_a should be blocked
        $resultA = $this->limiter->check('user_a', 5, 60);
        $this->assertFalse($resultA['allowed']);

        // user_b should still be allowed
        $resultB = $this->limiter->check('user_b', 5, 60);
        $this->assertTrue($resultB['allowed']);
    }

    public function testResetClearsRateLimit()
    {
        // Exhaust limit
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('test_user', 5, 60);
        }

        // Verify blocked
        $result = $this->limiter->check('test_user', 5, 60);
        $this->assertFalse($result['allowed']);

        // Reset
        $this->limiter->reset('test_user');

        // Should be allowed again
        $result = $this->limiter->check('test_user', 5, 60);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(4, $result['remaining']);
    }

    public function testCleanupRemovesOldFiles()
    {
        // Create some requests
        $this->limiter->check('user_a', 5, 60);
        $this->limiter->check('user_b', 5, 60);

        // Make the files appear old
        $files = glob($this->testCacheDir . DS . '*.ratelimit');
        foreach ($files as $file) {
            touch($file, time() - 7200); // 2 hours ago
        }

        $cleaned = $this->limiter->cleanup(3600); // 1 hour max age
        $this->assertEquals(2, $cleaned);

        // Verify files are gone
        $remaining = glob($this->testCacheDir . DS . '*.ratelimit');
        $this->assertCount(0, $remaining);
    }

    public function testCleanupDoesNotRemoveRecentFiles()
    {
        $this->limiter->check('user_a', 5, 60);

        $cleaned = $this->limiter->cleanup(3600);
        $this->assertEquals(0, $cleaned);

        $remaining = glob($this->testCacheDir . DS . '*.ratelimit');
        $this->assertCount(1, $remaining);
    }

    public function testDefaultLimitAndWindow()
    {
        $result = $this->limiter->check('test_user');

        $this->assertEquals(RateLimiter::DEFAULT_LIMIT, $result['limit']);
        $this->assertTrue($result['allowed']);
    }

    public function testResetNonExistentKeyReturnsTrue()
    {
        $result = $this->limiter->reset('nonexistent_key');
        $this->assertTrue($result);
    }

    public function testCleanupOnNonExistentDirectoryReturnsZero()
    {
        $limiter = new RateLimiter('/nonexistent/path/that/does/not/exist');
        $result = $limiter->cleanup(3600);
        $this->assertEquals(0, $result);
    }
}
