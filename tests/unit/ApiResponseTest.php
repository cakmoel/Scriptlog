<?php

/**
 * ApiResponse Unit Tests
 *
 * Tests for the pure logic methods of ApiResponse.
 * Methods that call header()/exit() are tested via integration tests.
 */
class ApiResponseTest extends \PHPUnit\Framework\TestCase
{
    private $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    // ========================================
    // ETag Match Tests (checkEtagMatch)
    // ========================================

    public function testCheckEtagMatchExactMatch()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"abc123"';
        $this->assertTrue(ApiResponse::checkEtagMatch('abc123'));
    }

    public function testCheckEtagMatchAlreadyQuoted()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"abc123"';
        $this->assertTrue(ApiResponse::checkEtagMatch('"abc123"'));
    }

    public function testCheckEtagMatchWeakValidatorClient()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"abc123"';
        $this->assertTrue(ApiResponse::checkEtagMatch('abc123'));
    }

    public function testCheckEtagMatchWeakValidatorServer()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"abc123"';
        $this->assertTrue(ApiResponse::checkEtagMatch('W/abc123'));
    }

    public function testCheckEtagMatchMultipleEtags()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"xyz789", "abc123", "def456"';
        $this->assertTrue(ApiResponse::checkEtagMatch('abc123'));
    }

    public function testCheckEtagMatchWildcard()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '*';
        $this->assertTrue(ApiResponse::checkEtagMatch('anything'));
    }

    public function testCheckEtagNoMatch()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"different"';
        $this->assertFalse(ApiResponse::checkEtagMatch('abc123'));
    }

    public function testCheckEtagNoHeader()
    {
        unset($_SERVER['HTTP_IF_NONE_MATCH']);
        $this->assertFalse(ApiResponse::checkEtagMatch('abc123'));
    }

    public function testCheckEtagEmptyHeader()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '';
        $this->assertFalse(ApiResponse::checkEtagMatch('abc123'));
    }

    // ========================================
    // Modified Since Tests (checkModifiedSince)
    // ========================================

    public function testCheckModifiedSinceNotModified()
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Mon, 15 Jan 2024 10:05:00 GMT';
        $resourceTime = strtotime('Mon, 15 Jan 2024 10:00:00 GMT');
        $this->assertTrue(ApiResponse::checkModifiedSince($resourceTime));
    }

    public function testCheckModifiedSinceModified()
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Mon, 15 Jan 2024 10:00:00 GMT';
        $resourceTime = strtotime('Mon, 15 Jan 2024 10:05:00 GMT');
        $this->assertFalse(ApiResponse::checkModifiedSince($resourceTime));
    }

    public function testCheckModifiedSinceNoHeader()
    {
        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        $this->assertFalse(ApiResponse::checkModifiedSince(time()));
    }

    public function testCheckModifiedSinceInvalidDate()
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'invalid-date';
        $this->assertFalse(ApiResponse::checkModifiedSince(time()));
    }

    public function testCheckModifiedSinceEqualTime()
    {
        $time = time();
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s', $time) . ' GMT';
        $this->assertTrue(ApiResponse::checkModifiedSince($time));
    }

    // ========================================
    // Accept Header Validation Tests
    // ========================================

    public function testValidateAcceptWildcard()
    {
        $_SERVER['HTTP_ACCEPT'] = '*/*';
        $this->assertTrue(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptJson()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $this->assertTrue(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptNoHeader()
    {
        unset($_SERVER['HTTP_ACCEPT']);
        $this->assertTrue(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptEmptyHeader()
    {
        $_SERVER['HTTP_ACCEPT'] = '';
        $this->assertTrue(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptUnsupportedType()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xml';
        $this->assertFalse(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptWithQualityFactor()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json; q=0.9, application/xml; q=0.8';
        $this->assertTrue(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptMultipleTypes()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html, application/json, */*';
        $this->assertTrue(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptMainTypeWildcard()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/*';
        $this->assertTrue(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptWrongMainType()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/*';
        $this->assertFalse(ApiResponse::validateAccept(['application/json']));
    }

    public function testValidateAcceptMultipleSupportedTypes()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xml';
        $this->assertTrue(ApiResponse::validateAccept(['application/json', 'application/xml']));
    }

    // ========================================
    // Constants Tests
    // ========================================

    public function testHttpStatusCodes()
    {
        $this->assertEquals(200, ApiResponse::HTTP_OK);
        $this->assertEquals(201, ApiResponse::HTTP_CREATED);
        $this->assertEquals(204, ApiResponse::HTTP_NO_CONTENT);
        $this->assertEquals(400, ApiResponse::HTTP_BAD_REQUEST);
        $this->assertEquals(401, ApiResponse::HTTP_UNAUTHORIZED);
        $this->assertEquals(403, ApiResponse::HTTP_FORBIDDEN);
        $this->assertEquals(404, ApiResponse::HTTP_NOT_FOUND);
        $this->assertEquals(405, ApiResponse::HTTP_METHOD_NOT_ALLOWED);
        $this->assertEquals(406, ApiResponse::HTTP_NOT_ACCEPTABLE);
        $this->assertEquals(409, ApiResponse::HTTP_CONFLICT);
        $this->assertEquals(422, ApiResponse::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertEquals(429, ApiResponse::HTTP_TOO_MANY_REQUESTS);
        $this->assertEquals(500, ApiResponse::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertEquals(503, ApiResponse::HTTP_SERVICE_UNAVAILABLE);
    }

    public function testCacheTtlConstant()
    {
        $this->assertEquals(300, ApiResponse::CACHE_TTL);
    }

    public function testRateLimitConstants()
    {
        $this->assertEquals(60, ApiResponse::RATE_LIMIT);
        $this->assertEquals(60, ApiResponse::RATE_WINDOW);
    }

    // ========================================
    // Method Existence Tests
    // ========================================

    public function testAllPublicMethodsExist()
    {
        $methods = [
            'success', 'created', 'noContent', 'error',
            'badRequest', 'unauthorized', 'forbidden', 'notAcceptable',
            'conflict', 'notFound', 'unprocessableEntity', 'tooManyRequests',
            'methodNotAllowed', 'paginated', 'validateAccept',
            'withEtag', 'withLastModified', 'withLocation',
            'checkEtagMatch', 'checkModifiedSince', 'notModified',
            'setCorsHeaders', 'setRateLimitHeaders', 'initRateLimit'
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists('ApiResponse', $method),
                "Method '$method' does not exist on ApiResponse"
            );
        }
    }
}
