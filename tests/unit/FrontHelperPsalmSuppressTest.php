<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * FrontHelper Deprecation Test
 *
 * The static FrontHelper facade methods were deprecated in favour of the
 * Scriptlog\Service\FrontService instance. This verifies the deprecation
 * markers are present so static callers are steered to the service layer.
 */
class FrontHelperPsalmSuppressTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents(__DIR__ . '/../../src/lib/core/FrontHelper.php');
    }

    public function testGrabTagListsIsDeprecated(): void
    {
        $this->assertStringContainsString('@deprecated Use Scriptlog\Service\FrontService::getTagLists()', $this->source);
    }

    public function testGrabTagListsMethodExists(): void
    {
        if (!class_exists('FrontHelper')) {
            $this->markTestSkipped('FrontHelper class not found');
        }
        $this->assertTrue(method_exists('FrontHelper', 'grabTagLists'));
    }

    public function testGrabPreparedFrontArchiveIsDeprecated(): void
    {
        $this->assertStringContainsString('@deprecated Use Scriptlog\Service\FrontService::getArchivePosts()', $this->source);
    }

    public function testGrabPreparedFrontArchiveMethodExists(): void
    {
        if (!class_exists('FrontHelper')) {
            $this->markTestSkipped('FrontHelper class not found');
        }
        $this->assertTrue(method_exists('FrontHelper', 'grabPreparedFrontArchive'));
    }

    public function testFrontGalleriesIsDeprecated(): void
    {
        $this->assertStringContainsString('@deprecated Use Scriptlog\Service\FrontService::getGalleries()', $this->source);
    }

    public function testGrabPreparedFrontGalleriesMethodExists(): void
    {
        if (!class_exists('FrontHelper')) {
            $this->markTestSkipped('FrontHelper class not found');
        }
        $this->assertTrue(method_exists('FrontHelper', 'grabPreparedFrontGalleries'));
    }

    public function testDeprecationMarkersCoverServiceBoundary(): void
    {
        $occurrences = substr_count($this->source, '@deprecated Use Scriptlog\Service\FrontService');
        $this->assertGreaterThanOrEqual(3, $occurrences, 'Should have at least 3 @deprecated annotations');
    }
}
