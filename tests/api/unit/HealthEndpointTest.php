<?php
/**
 * Health Endpoint Test
 *
 * End-to-end tests for the GET /api/v1/health route registered in
 * api/index.php. The closure returns status "healthy", a timestamp in
 * Y-m-d H:i:s format, and the runtime PHP version inside the standard
 * ApiResponse envelope (data.status, data.timestamp, data.php_version).
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';

class HealthEndpointTest extends ApiTestCase
{
    /**
     * The health check responds 200 with a healthy status payload.
     *
     * @return void
     */
    public function testHealthCheckReturns200()
    {
        $response = $this->httpRequest('GET', '/health');

        $this->assertSame(200, $response['code']);

        $body = json_decode($response['body'], true);
        $this->assertIsArray($body);
        $this->assertTrue($body['success'] ?? false);
        $this->assertSame('healthy', $body['data']['status'] ?? null);
    }

    /**
     * The health check includes a non-empty Y-m-d H:i:s timestamp.
     *
     * @return void
     */
    public function testHealthCheckReturnsTimestamp()
    {
        $response = $this->httpRequest('GET', '/health');

        $body = json_decode($response['body'], true);
        $timestamp = $body['data']['timestamp'] ?? '';

        $this->assertIsString($timestamp);
        $this->assertNotSame('', $timestamp);
        $this->assertSame(1, preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp));
    }

    /**
     * The health check reports the current PHP version.
     *
     * @return void
     */
    public function testHealthCheckReturnsPhpVersion()
    {
        $response = $this->httpRequest('GET', '/health');

        $body = json_decode($response['body'], true);
        $this->assertSame(PHP_VERSION, $body['data']['php_version'] ?? null);
    }
}
