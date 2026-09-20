<?php

/**
 * ApiAuth Unit Tests
 *
 * Tests for API authentication methods that do NOT require a database:
 * - Header parsing (getApiKey, getBearerToken)
 * - State queries (isAuthenticated, getUser, getUserId, getUserLevel, getAuthType)
 * - Permission checks (hasPermission)
 * - CSRF token generation
 * - Client IP detection
 *
 * Database-dependent methods (authenticateWithApiKey, authenticateWithToken,
 * generateApiKey, revokeApiKey, logAccess) are covered by integration tests.
 */

use Scriptlog\Core\ApiAuth;

class ApiAuthTest extends \PHPUnit\Framework\TestCase
{
    private $serverBackup;

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testGetApiKeyFromHeader()
    {
        $_SERVER['HTTP_X_API_KEY'] = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getApiKey');
        $method->setAccessible(true);
        $this->assertSame('a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2', $method->invoke(null));
    }

    public function testGetApiKeyIgnoresQueryString()
    {
        unset($_SERVER['HTTP_X_API_KEY']);
        $_GET['api_key'] = 'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3';
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getApiKey');
        $method->setAccessible(true);
        $this->assertNull($method->invoke(null));
    }

    public function testGetApiKeyReturnsNullWhenNotPresent()
    {
        unset($_SERVER['HTTP_X_API_KEY']);
        unset($_GET['api_key']);
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getApiKey');
        $method->setAccessible(true);
        $this->assertNull($method->invoke(null));
    }

    public function testGetBearerTokenFromAuthorizationHeader()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer my-test-token-value-here';
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getBearerToken');
        $method->setAccessible(true);
        $this->assertSame('my-test-token-value-here', $method->invoke(null));
    }

    public function testGetBearerTokenReturnsNullWhenNotPresent()
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getBearerToken');
        $method->setAccessible(true);
        $this->assertNull($method->invoke(null));
    }

    public function testGetBearerTokenIgnoresNonBearerAuth()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic dXNlcjpwYXNz';
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getBearerToken');
        $method->setAccessible(true);
        $this->assertNull($method->invoke(null));
    }

    public function testInitialAuthStateIsFalse()
    {
        $this->assertFalse(ApiAuth::isAuthenticated());
        $this->assertNull(ApiAuth::getUser());
        $this->assertNull(ApiAuth::getUserId());
        $this->assertNull(ApiAuth::getUserLevel());
    }

    public function testHasPermissionReturnsFalseWhenNotAuthenticated()
    {
        $this->assertFalse(ApiAuth::hasPermission('administrator'));
    }

    public function testHasPermissionWithArray()
    {
        $this->assertFalse(ApiAuth::hasPermission(['administrator', 'editor']));
    }

    public function testGetClientIpFromRemoteAddr()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getClientIp');
        $method->setAccessible(true);
        $this->assertSame('192.168.1.100', $method->invoke(null));
    }

    public function testGetClientIpReturnsZeroWhenNoIp()
    {
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getClientIp');
        $method->setAccessible(true);
        $this->assertSame('0.0.0.0', $method->invoke(null));
    }

    public function testGetClientIpIgnoresForwardedHeaders()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.5';
        $method = (new \ReflectionClass(ApiAuth::class))->getMethod('getClientIp');
        $method->setAccessible(true);
        $this->assertSame('10.0.0.1', $method->invoke(null));
    }

    public function testGenerateCsrfTokenWithoutSessionReturnsEmpty()
    {
        $this->assertSame('', ApiAuth::generateCsrfToken());
    }

    public function testValidateCsrfForWriteSkipsOnGetMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }
}
