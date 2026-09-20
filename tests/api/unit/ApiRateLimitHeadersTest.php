<?php
/**
 * ApiRateLimitHeadersTest
 *
 * Regression tests (F2) locking in the rate-limit header fix.
 *
 * Rate limiting is applied in api/index.php before dispatch: GET requests
 * consume the "read" namespace, write methods the "write" namespace. The
 * result is stored in $GLOBALS['_api_rate_result'] and ApiResponse::send()
 * emits X-RateLimit-Limit / X-RateLimit-Remaining / X-RateLimit-Reset (plus
 * Retry-After when the window is exhausted).
 *
 * Note: RateLimiter::check() does NOT include a 'namespace' key in its
 * result — the namespace only prefixes the on-disk counter key — so the
 * namespace tests verify counter separation rather than a literal result
 * field.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class ApiRateLimitHeadersTest extends ApiTestCase
{
    /**
     * API key value sent in the request header.
     *
     * Since rate limiting is keyed on the client IP (never on the
     * attacker-controlled X-API-Key header), sending this header must NOT
     * grant the request a separate on-disk bucket. Keeping it here doubles as
     * a regression check for the bucket-bypass fix.
     */
    private const RATE_KEY = 'phpunit-rate-limit-counter';

    /**
     * Ensure a clean counter for this test's key before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRateFile();
    }

    /**
     * Clean up the counter file so no counter is left exhausted.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->clearRateFile();
        parent::tearDown();
    }

    /**
     * Path of the on-disk counter file for this test's client.
     *
     * The built-in server is bound to 127.0.0.1, so REMOTE_ADDR (and thus
     * get_ip_address()) is always "127.0.0.1". RateLimiter::getClientKey()
     * produces "ip:127.0.0.1" regardless of any X-API-Key header, and
     * RateLimiter::check() prepends the namespace ("read" for GET) before
     * hashing the counter filename.
     *
     * @return string
     */
    private function rateFile()
    {
        return APP_ROOT . DS . 'public' . DS . 'cache' . DS . 'rate_limit'
            . DS . md5('read:ip:127.0.0.1') . '.ratelimit';
    }

    /**
     * Remove this client's bucket file if present.
     *
     * @return void
     */
    private function clearRateFile()
    {
        $file = $this->rateFile();
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Successful responses emit the rate-limit headers.
     *
     * @return void
     */
    public function testSuccessfulResponseEmitsRateLimitHeaders()
    {
        $response = $this->httpRequest('GET', '/health', ['X-API-Key' => self::RATE_KEY]);

        $this->assertSame(200, $response['code']);

        $this->assertArrayHasKey('x-ratelimit-limit', $response['headers']);
        $this->assertArrayHasKey('x-ratelimit-remaining', $response['headers']);
        $this->assertArrayHasKey('x-ratelimit-reset', $response['headers']);

        $limit = (int)$response['headers']['x-ratelimit-limit'];
        $remaining = (int)$response['headers']['x-ratelimit-remaining'];

        $this->assertGreaterThan(0, $limit);
        $this->assertSame($limit - 1, $remaining);
    }

    /**
     * GET requests and write requests consume independent counters.
     *
     * api/index.php maps GET to the "read" namespace and POST/PUT/PATCH/
     * DELETE to the "write" namespace. RateLimiter::check() does not echo
     * the namespace back, so separation is proven by exhausting one
     * namespace while the other stays available.
     *
     * @return void
     */
    public function testReadAndWriteNamespacesAreIndependent()
    {
        $cacheDir = sys_get_temp_dir() . DS . 'ratelimit_ns_' . uniqid();
        $limiter = new \Scriptlog\Core\RateLimiter($cacheDir);
        $key = 'phpunit-ns';

        $read1 = $limiter->check($key, 1, 60, 'read');
        $this->assertTrue($read1['allowed']);

        $read2 = $limiter->check($key, 1, 60, 'read');
        $this->assertFalse($read2['allowed']);
        $this->assertSame(0, $read2['remaining']);
        $this->assertGreaterThan(0, $read2['retry_after']);

        $write = $limiter->check($key, 1, 60, 'write');
        $this->assertTrue($write['allowed']);

        $write2 = $limiter->check($key, 1, 60, 'write');
        $this->assertFalse($write2['allowed']);

        @unlink($cacheDir . DS . md5('read:' . $key) . '.ratelimit');
        @unlink($cacheDir . DS . md5('write:' . $key) . '.ratelimit');
        @rmdir($cacheDir);
    }

    /**
     * Exceeding the read window returns 429 with Retry-After.
     *
     * A counter file pre-seeded with 200 timestamps inside the window forces
     * the first request over any configured read limit, so the real
     * api/index.php path returns 429 RATE_LIMIT_EXCEEDED with Retry-After.
     *
     * @return void
     */
    public function testOverLimitRequestReturns429WithRetryAfter()
    {
        $now = time();
        $timestamps = array_fill(0, 200, $now);

        $dir = dirname($this->rateFile());
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($this->rateFile(), json_encode($timestamps), LOCK_EX);

        $response = $this->httpRequest('GET', '/health', ['X-API-Key' => self::RATE_KEY]);

        $this->assertSame(429, $response['code']);
        $this->assertArrayHasKey('retry-after', $response['headers']);
        $this->assertGreaterThan(0, (int)$response['headers']['retry-after']);

        $body = json_decode($response['body'], true);
        $this->assertIsArray($body);
        $this->assertFalse($body['success'] ?? true);
        $this->assertSame('RATE_LIMIT_EXCEEDED', $body['error']['code'] ?? null);
    }
}
