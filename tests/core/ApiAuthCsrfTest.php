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
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testHasApiOrBearerAuthWithBearerToken(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer some-token';
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testHasApiOrBearerAuthWithRedirectBearerToken(): void
    {
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer some-token';
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testHasApiOrBearerAuthReturnsFalseWithoutHeaders(): void
    {
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null));
    }

    public function testHasApiOrBearerAuthWithEmptyApiKey(): void
    {
        $_SERVER['HTTP_X_API_KEY'] = '';
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null));
    }

    public function testHasApiOrBearerAuthAcceptsCaseInsensitiveBearer(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'bearer some-token';
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testHasApiOrBearerAuthWithNonBearerAuthHeader(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic dXNlcjpwYXNz';
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null));
    }

    public function testHasApiOrBearerAuthPrefersApiKeyOverBearer(): void
    {
        $_SERVER['HTTP_X_API_KEY'] = 'api-key-value';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token';
        $method = (new ReflectionClass('ApiAuth'))->getMethod('hasApiOrBearerAuth');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null));
    }

    public function testGenerateCsrfTokenWithoutSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $this->assertEquals('', ApiAuth::generateCsrfToken());
    }

    public function testGenerateCsrfTokenWithSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Session cannot be started in this environment');
        }
        $token = ApiAuth::generateCsrfToken();
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testGenerateCsrfTokenStoredInSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Session cannot be started in this environment');
        }
        unset($_SESSION['csrf_api_write']);
        $token = ApiAuth::generateCsrfToken();
        $this->assertArrayHasKey('csrf_api_write', $_SESSION);
        $this->assertSame($token, $_SESSION['csrf_api_write']);
    }

    public function testGenerateCsrfTokenReturnsUniqueTokens(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Session cannot be started in this environment');
        }
        $this->assertNotEquals(
            ApiAuth::generateCsrfToken(),
            ApiAuth::generateCsrfToken()
        );
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

    public function testValidateCsrfForWriteSkipsTraceMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'TRACE';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsConnectMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'CONNECT';
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

    public function testValidateCsrfForWriteSkipsDeleteWithApiKey(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['HTTP_X_API_KEY'] = 'test-key';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsPatchWithBearer(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsPostWithoutSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            session_destroy();
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteSkipsPutWithoutSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            session_destroy();
        }
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testValidateCsrfForWriteWithoutRequestMethod(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testFullCsrfFlowPost(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Session cannot be started in this environment');
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = ApiAuth::generateCsrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testFullCsrfFlowPut(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Session cannot be started in this environment');
        }
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $token = ApiAuth::generateCsrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }

    public function testFullCsrfFlowDelete(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->markTestSkipped('Session cannot be started in this environment');
        }
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $token = ApiAuth::generateCsrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        ApiAuth::validateCsrfForWrite();
        $this->assertTrue(true);
    }
}
