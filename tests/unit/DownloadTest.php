<?php
/**
 * Download Feature Unit Tests
 * 
 * Tests for DownloadHandler, DownloadSettings utilities
 * 
 * @version 1.0
 * @since 1.0
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class DownloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists('DownloadHandler')) {
            $this->markTestSkipped('DownloadHandler class not found');
        }
    }

    public function testIsExpiredWithFutureTimestamp(): void
    {
        $futureTime = time() + 3600; // 1 hour from now
        $result = DownloadHandler::isExpired($futureTime);
        $this->assertFalse($result);
    }

    public function testIsExpiredWithPastTimestamp(): void
    {
        $pastTime = time() - 3600; // 1 hour ago
        $result = DownloadHandler::isExpired($pastTime);
        $this->assertTrue($result);
    }

    public function testIsHotlinkingAllowedWithEmptyDomains(): void
    {
        $result = DownloadHandler::isHotlinkingAllowed('http://example.com', []);
        $this->assertTrue($result);
    }

    public function testIsHotlinkingAllowedWithAllowedDomain(): void
    {
        $result = DownloadHandler::isHotlinkingAllowed('http://example.com/page', ['example.com']);
        $this->assertTrue($result);
    }

    public function testIsHotlinkingAllowedWithBlockedDomain(): void
    {
        $result = DownloadHandler::isHotlinkingAllowed('http://evil.com/page', ['example.com']);
        $this->assertFalse($result);
    }

    public function testSanitizeFilePathBlocksTraversal(): void
    {
        $result = DownloadHandler::sanitizeFilePath('../../etc/passwd');
        $this->assertEquals('passwd', $result);
    }

    public function testSanitizeFilePathBlocksNullBytes(): void
    {
        $result = DownloadHandler::sanitizeFilePath("file.txt\0evil.php");
        $this->assertFalse($result);
    }

    public function testSanitizeFilePathAllowsValidFilename(): void
    {
        $result = DownloadHandler::sanitizeFilePath("document.pdf");
        $this->assertEquals('document.pdf', $result);
    }

    public function testValidateDownloadRequestWithValidIdentifier(): void
    {
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = DownloadHandler::validateDownloadRequest($validUuid);
        $this->assertTrue($result);
    }

    public function testValidateDownloadRequestWithInvalidIdentifier(): void
    {
        $invalidUuid = 'not-a-uuid';
        $result = DownloadHandler::validateDownloadRequest($invalidUuid);
        $this->assertFalse($result);
    }
}

class DownloadSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists('DownloadSettings')) {
            $this->markTestSkipped('DownloadSettings class not found');
        }
    }

    public function testGetAllowedMimeTypesReturnsArray(): void
    {
        $result = DownloadSettings::getAllowedMimeTypes();
        $this->assertIsArray($result);
    }

    public function testGetDownloadExpiryReturnsInteger(): void
    {
        $result = DownloadSettings::getDownloadExpiry();
        $this->assertIsInt($result);
    }

    public function testGetDownloadExpiryDefaultValue(): void
    {
        $result = DownloadSettings::getDownloadExpiry();
        $this->assertEquals(8, $result);
    }

    public function testIsHotlinkProtectionEnabledReturnsBoolean(): void
    {
        $result = DownloadSettings::isHotlinkProtectionEnabled();
        $this->assertIsBool($result);
    }

    public function testGetAllowedDomainsReturnsArray(): void
    {
        $result = DownloadSettings::getAllowedDomains();
        $this->assertIsArray($result);
    }

    public function testGetSupportUrlReturnsString(): void
    {
        $result = DownloadSettings::getSupportUrl();
        $this->assertIsString($result);
    }

    public function testGetSupportLabelReturnsString(): void
    {
        $result = DownloadSettings::getSupportLabel();
        $this->assertIsString($result);
    }

    public function testGetSupportLabelDefaultValue(): void
    {
        $result = DownloadSettings::getSupportLabel();
        $this->assertEquals('Support', $result);
    }

    public function testGetAllSettingsReturnsArray(): void
    {
        $result = DownloadSettings::getAllSettings();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed_mime_types', $result);
        $this->assertArrayHasKey('expiry_hours', $result);
        $this->assertArrayHasKey('hotlink_protection', $result);
        $this->assertArrayHasKey('allowed_domains', $result);
        $this->assertArrayHasKey('support_url', $result);
        $this->assertArrayHasKey('support_label', $result);
    }

    public function testDefaultMimeTypesContainsPdf(): void
    {
        $mimeTypes = DownloadSettings::DEFAULT_MIME_TYPES;
        $this->assertContains('application/pdf', $mimeTypes);
    }

    public function testDefaultMimeTypesContainsZip(): void
    {
        $mimeTypes = DownloadSettings::DEFAULT_MIME_TYPES;
        $this->assertContains('application/zip', $mimeTypes);
    }
}

class DownloadServiceTest extends TestCase
{
    private $downloadService;
    private $downloadModel;
    private $mediaDao;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists('DownloadService')) {
            $this->markTestSkipped('DownloadService class not found');
        }
        
        if (!class_exists('DownloadModel')) {
            $this->markTestSkipped('DownloadModel class not found');
        }
        
        if (!class_exists('MediaDao')) {
            $this->markTestSkipped('MediaDao class not found');
        }

        try {
            $this->downloadModel = new DownloadModel();
            $this->mediaDao = new MediaDao();
            $this->downloadService = new DownloadService($this->downloadModel, $this->mediaDao);
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    public function testGenerateDownloadIdentifier(): void
    {
        $identifier = $this->downloadService->generateDownloadIdentifier();
        $this->assertNotEmpty($identifier);
        $this->assertIsString($identifier);
    }

    public function testGetDownloadStatisticsReturnsArray(): void
    {
        $result = $this->downloadService->getDownloadStatistics();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_downloads', $result);
        $this->assertArrayHasKey('active_links', $result);
        $this->assertArrayHasKey('expired_links', $result);
        $this->assertArrayHasKey('total_files', $result);
    }

    public function testGetFileTypeDistributionReturnsArray(): void
    {
        $result = $this->downloadService->getFileTypeDistribution();
        $this->assertIsArray($result);
    }

    public function testGetAllDownloadsReturnsArray(): void
    {
        $result = $this->downloadService->getAllDownloads();
        $this->assertIsArray($result);
    }
}
