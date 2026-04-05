<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * RateLimiter
 *
 * File-based sliding window rate limiter for API endpoints.
 * Uses IP address as the default key. Stores request timestamps
 * in per-key files under the cache directory.
 *
 * Compatible with PHP 7.4+.
 *
 * @category  Utility Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 */
class RateLimiter
{
    /**
     * Default maximum requests per window
     */
    public const DEFAULT_LIMIT = 60;

    /**
     * Default window size in seconds
     */
    public const DEFAULT_WINDOW = 60;

    /**
     * Directory for rate limit data files
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Constructor
     *
     * @param string|null $cacheDir Override cache directory path
     */
    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?: (APP_ROOT . DS . 'public' . DS . 'cache' . DS . 'rate_limit');
        $this->ensureCacheDir();
    }

    /**
     * Ensure the cache directory exists and is writable
     *
     * @return void
     */
    private function ensureCacheDir()
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Check if the current request exceeds the rate limit
     *
     * @param string $key Unique identifier (default: client IP)
     * @param int $limit Maximum requests allowed in the window
     * @param int $window Window size in seconds
     * @return array Result with limit, remaining, reset, retry_after, allowed
     */
    public function check($key = null, $limit = self::DEFAULT_LIMIT, $window = self::DEFAULT_WINDOW)
    {
        $key = $key ?: $this->getClientKey();
        $file = $this->cacheDir . DS . md5($key) . '.ratelimit';
        $now = time();
        $windowStart = $now - $window;

        // Read existing timestamps
        $timestamps = $this->readTimestamps($file, $windowStart);

        // Count requests in current window
        $count = count($timestamps);

        if ($count >= $limit) {
            // Rate limit exceeded
            $oldestInWindow = min($timestamps);
            $resetTime = $oldestInWindow + $window;
            $retryAfter = $resetTime - $now;

            return [
                'limit' => $limit,
                'remaining' => 0,
                'reset' => $resetTime,
                'retry_after' => max(1, $retryAfter),
                'allowed' => false,
            ];
        }

        // Add current request timestamp
        $timestamps[] = $now;
        $this->writeTimestamps($file, $timestamps, $windowStart);

        $resetTime = $now + $window;

        return [
            'limit' => $limit,
            'remaining' => $limit - $count - 1,
            'reset' => $resetTime,
            'retry_after' => 0,
            'allowed' => true,
        ];
    }

    /**
     * Read timestamps from file, filtering out expired ones
     *
     * @param string $file File path
     * @param int $windowStart Earliest valid timestamp
     * @return array Valid timestamps
     */
    private function readTimestamps($file, $windowStart)
    {
        if (!file_exists($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if ($content === false || $content === '') {
            return [];
        }

        $timestamps = json_decode($content, true);
        if (!is_array($timestamps)) {
            return [];
        }

        // Filter out timestamps outside the current window
        return array_values(array_filter($timestamps, function ($ts) use ($windowStart) {
            return $ts > $windowStart;
        }));
    }

    /**
     * Write timestamps to file
     *
     * @param string $file File path
     * @param array $timestamps Timestamps to write
     * @param int $windowStart Earliest valid timestamp
     * @return void
     */
    private function writeTimestamps($file, $timestamps, $windowStart)
    {
        // Only keep timestamps within the window to keep file small
        $valid = array_values(array_filter($timestamps, function ($ts) use ($windowStart) {
            return $ts > $windowStart;
        }));

        @file_put_contents($file, json_encode($valid), LOCK_EX);
    }

    /**
     * Generate a unique key for the current client
     *
     * Uses X-API-Key header if present, otherwise IP address.
     *
     * @return string
     */
    private function getClientKey()
    {
        $apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
        if (!empty($apiKey)) {
            return 'apikey:' . $apiKey;
        }

        $ip = get_ip_address();
        return 'ip:' . $ip;
    }

    /**
     * Reset rate limit for a specific key
     *
     * @param string $key Unique identifier
     * @return bool
     */
    public function reset($key = null)
    {
        $key = $key ?: $this->getClientKey();
        $file = $this->cacheDir . DS . md5($key) . '.ratelimit';

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Clean up expired rate limit files
     *
     * @param int $maxAge Maximum age in seconds before file is considered stale
     * @return int Number of files cleaned
     */
    public function cleanup($maxAge = 3600)
    {
        $cleaned = 0;
        $cutoff = time() - $maxAge;

        if (!is_dir($this->cacheDir)) {
            return 0;
        }

        $files = glob($this->cacheDir . DS . '*.ratelimit');
        if (!$files) {
            return 0;
        }

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}
