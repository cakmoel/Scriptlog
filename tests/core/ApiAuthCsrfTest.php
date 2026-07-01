<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/lib/core/ApiAuth.php';

class ApiAuthCsrfTest extends TestCase
{
    private $originalServer;
    private $originalSession;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $this->originalSession = $_SESSION ?? [];
        $_SERVER = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_SESSION = $this->originalSession;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testHasApiOrBearerAuthWithApiKeyHeader(): void
    {
        $_SERVER['HTTP_X_API_KEY'] = 'some-api-key';
        $ref = new ReflectionClass('ApiAuth');
        $method = $ref->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testHasApiOrBearerAuthWithBearerToken(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer some-token';
        $ref = new ReflectionClass('ApiAuth');
        $method = $ref->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testHasApiOrBearerAuthWithRedirectBearerToken(): void
    {
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer some-token';
        $ref = new ReflectionClass('ApiAuth');
        $method = $ref->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testHasApiOrBearerAuthReturnsFalseWithoutHeaders(): void
    {
        $ref = new ReflectionClass('ApiAuth');
        $method = $ref->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null));
    }

    public function testHasApiOrBearerAuthWithEmptyApiKey(): void
    {
        $_SERVER['HTTP_X_API_KEY'] = '';
        $ref = new ReflectionClass('ApiAuth');
        $method = $ref->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null));
    }

    public function testGenerateCsrfTokenWithoutSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $token = ApiAuth::generateCsrfToken();
        $this->assertEquals('', $token);
    }

    public function testGenerateCsrfTokenWithSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = ApiAuth::generateCsrfToken();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->assertEquals(64, strlen($token));
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
            $this->assertEquals($token, $_SESSION['csrf_api_write']);
        } else {
            $this->assertEquals('', $token);
        }
    }

    public function testGenerateCsrfTokenStoresInSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Sessions not available in this environment');
        }
        $token1 = ApiAuth::generateCsrfToken();
        $token2 = ApiAuth::generateCsrfToken();
        $this->assertNotEquals($token1, $token2);
    }

    public function testValidateCsrfForWriteSkipsGetMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsHeadMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsOptionsMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsWhenApiKeyPresent(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_API_KEY'] = 'test-key';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsWhenBearerTokenPresent(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testFullCsrfFlow(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Sessions not available in this environment');
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = ApiAuth::generateCsrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteMethodPatching(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $_SERVER['HTTP_X_API_KEY'] = 'test-key';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWritePostWithoutSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            session_destroy();
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }
}
