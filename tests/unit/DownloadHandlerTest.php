<?php
/**
 * Download Feature Unit Tests
 *
 * Tests for DownloadHandler utility class.
 *
 * @category   UnitTests
 * @version    1.0.0
 * @since     April 2026
 * @license   MIT
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lib/utility/download-handler.php';

/**
 * @coversDefaultClass \DownloadHandler
 */
class DownloadHandlerTest extends TestCase
{
    /**
     * @covers ::isExpired
     * @dataProvider isExpiredProvider
     */
    public function testIsExpired($timestamp, $expected): void
    {
        $result = DownloadHandler::isExpired($timestamp);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers ::isHotlinkingAllowed
     * @dataProvider isHotlinkingAllowedProvider
     */
    public function testIsHotlinkingAllowed($referer, $allowedDomains, $expected): void
    {
        $result = DownloadHandler::isHotlinkingAllowed($referer, $allowedDomains);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers ::sanitizeFilePath
     * @dataProvider sanitizeFilePathProvider
     */
    public function testSanitizeFilePath($filename, $expected): void
    {
        $result = DownloadHandler::sanitizeFilePath($filename);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers ::validateDownloadRequest
     * @dataProvider validateDownloadRequestProvider
     */
    public function testValidateDownloadRequest($identifier, $expected): void
    {
        $result = DownloadHandler::validateDownloadRequest($identifier);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers ::setDownloadHeaders
     *
     * Verify the method does not throw exceptions when called.
     * Headers cannot be sent in test context (PHPUnit stdout), so we verify no exception is thrown.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetDownloadHeadersDoesNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();
        DownloadHandler::setDownloadHeaders('test.pdf', 'application/pdf', 1024);
    }

    /**
     * @covers ::getMimeType
     */
    public function testGetMimeTypeReturnsApplicationOctetStreamForNonexistentFile(): void
    {
        $result = DownloadHandler::getMimeType('/nonexistent/file.txt');
        $this->assertEquals('application/octet-stream', $result);
    }

    /**
     * @covers ::getMimeType
     */
    public function testGetMimeTypeReturnsProperType(): void
    {
        $tmpFile = sys_get_temp_dir() . '/test_mime_type_' . uniqid() . '.pdf';
        file_put_contents($tmpFile, 'test content');

        try {
            $result = DownloadHandler::getMimeType($tmpFile);
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        } finally {
            unlink($tmpFile);
        }
    }

    /**
     * @covers ::CHUNK_SIZE
     */
    public function testChunkSizeConstant(): void
    {
        $this->assertEquals(8192, DownloadHandler::CHUNK_SIZE);
    }

    /**
     * @covers ::DEFAULT_EXPIRY
     */
    public function testDefaultExpiryConstant(): void
    {
        $this->assertEquals(28800, DownloadHandler::DEFAULT_EXPIRY);
    }

    /**
     * @covers ::isMimeTypeAllowed
     */
    public function testIsMimeTypeAllowedReturnsTrue(): void
    {
        $result = DownloadHandler::isMimeTypeAllowed('application/pdf');
        $this->assertTrue($result);
    }

    /**
     * @covers ::isMimeTypeAllowed
     */
    public function testIsMimeTypeAllowedReturnsFalseForUnknownType(): void
    {
        $result = DownloadHandler::isMimeTypeAllowed('application/x-unknown');
        $this->assertFalse($result);
    }

    /**
     * Note: deliverFile() is intentionally omitted from unit testing.
     * It calls header(), http_response_code(), echo(), and exit() which are HTTP-layer
     * concerns that cannot be reliably exercised in PHPUnit's buffered context.
     * Integration tests should be used for this method.
     */

    // -- Data Providers -------------------------------------------------------

    public function isExpiredProvider(): array
    {
        return [
            'future timestamp' => [time() + 3600, false],
            'past timestamp'  => [time() - 3600, true],
            'current time'    => [time() + 1, false],
        ];
    }

    public function isHotlinkingAllowedProvider(): array
    {
        return [
            'empty domains'           => ['http://example.com', [], true],
            'no referer empty domains'=> [null, [], true],
            'no referer with domains'  => [null, ['example.com'], true],
            'allowed domain'          => ['http://example.com/page', ['example.com'], true],
            'blocked domain'         => ['http://evil.com/page', ['example.com'], false],
            'allowed domain w/ path' => ['https://example.com/path/to/file', ['example.com'], true],
            'subdomain blocked'      => ['http://sub.example.com', ['example.com'], false],
        ];
    }

    public function sanitizeFilePathProvider(): array
    {
        return [
            'valid filename'           => ['document.pdf', 'document.pdf'],
            'path traversal'         => ['../../etc/passwd', 'passwd'],
            'double path traversal'    => ['../../../etc/passwd', 'passwd'],
            'null byte in filename'   => ["file.txt\0evil.php", false],
            'path with null byte'    => ['path/to/file\0.txt', 'file\0.txt'],
            'simple filename'        => ['test.txt', 'test.txt'],
            'filename with spaces'    => ['my document.pdf', 'my document.pdf'],
            'filename with slash'    => ['subdir/document.pdf', 'document.pdf'],
        ];
    }

    public function validateDownloadRequestProvider(): array
    {
        return [
            'valid uuid'        => ['550e8400-e29b-41d4-a716-446655440000', true],
            'valid lowercase'=> ['a0b0c0d0-e1f2-3456-7890-a1b2c3d4e5f6', true],
            'valid uppercase'=> ['A0B0C0D0-E1F2-3456-7890-A1B2C3D4E5F6', true],
            'mixed case'   => ['AbC12345-DeF6-7890-Ab12-CdEf34567890', true],
            'invalid format'=> ['not-a-uuid', false],
            'empty string'  => ['', false],
            'too short'    => ['abc123', false],
            'too long'   => ['550e8400-e29b-41d4-a716-446655440000-extra', false],
            'invalid chars'=> ['gggggggg-eeee-dddd-aaaa-1234567890ab', false],
        ];
    }
}