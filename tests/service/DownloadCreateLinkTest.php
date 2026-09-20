<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Download Create Link Test
 * 
 * Tests for the create download link functionality.
 * Verifies that media files can get new download links after deletion.
 * 
 * @category Tests
 * @version 1.0
 */
class DownloadCreateLinkTest extends TestCase
{
    /**
     * Test that DownloadService::createDownloadRecord() method exists
     */
    public function testCreateDownloadRecordMethodExists(): void
    {
        $this->assertTrue(method_exists('DownloadService', 'createDownloadRecord'));
        
        $refMethod = new ReflectionMethod('DownloadService', 'createDownloadRecord');
        $this->assertEquals(2, $refMethod->getNumberOfParameters());
    }

    /**
     * Test that DownloadAdminController::createDownloadLink() method exists
     */
    public function testCreateDownloadLinkMethodExists(): void
    {
        $this->assertTrue(method_exists('DownloadAdminController', 'createDownloadLink'));
        
        $refMethod = new ReflectionMethod('DownloadAdminController', 'createDownloadLink');
        $this->assertEquals(2, $refMethod->getNumberOfParameters());
    }

    /**
     * Test that DownloadAdminController::bulkCreateDownloadLinks() method exists
     */
    public function testBulkCreateDownloadLinksMethodExists(): void
    {
        $this->assertTrue(method_exists('DownloadAdminController', 'bulkCreateDownloadLinks'));
        
        $refMethod = new ReflectionMethod('DownloadAdminController', 'bulkCreateDownloadLinks');
        $this->assertEquals(1, $refMethod->getNumberOfParameters());
    }

    /**
     * Test that admin/downloads.php has createLink action
     */
    public function testCreateLinkActionInDownloadsPage(): void
    {
        $content = file_get_contents(__DIR__ . '/../../admin/downloads.php');
        $this->assertStringContainsString("case 'createLink':", $content);
        $this->assertStringContainsString('createDownloadLink', $content);
    }

    /**
     * Test that all-media.php has Create Link button
     */
    public function testAllMediaHasCreateLinkButton(): void
    {
        $content = file_get_contents(__DIR__ . '/../../admin/ui/medialib/all-media.php');
        $this->assertStringContainsString('Create Link', $content);
        $this->assertStringContainsString('createLink', $content);
    }

    /**
     * Test that generateDownloadIdentifier method exists in DownloadService
     */
    public function testGenerateDownloadIdentifierExists(): void
    {
        $this->assertTrue(method_exists('DownloadService', 'generateDownloadIdentifier'));
    }

    /**
     * Test that DownloadSettings::getDownloadExpiry exists
     */
    public function testDownloadSettingsGetExpiryExists(): void
    {
        if (class_exists('DownloadSettings')) {
            $this->assertTrue(method_exists('DownloadSettings', 'getDownloadExpiry'));
        } else {
            $this->markTestSkipped('DownloadSettings class not available');
        }
    }
}
