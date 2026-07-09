<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class FrontHelperPsalmSuppressTest extends TestCase
{
    public function testGrabTagListsHasPsalmSuppress(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/FrontHelper.php');

        $this->assertStringContainsString('@psalm-suppress PossiblyUnusedMethod', $source);
    }

    public function testGrabTagListsMethodExists(): void
    {
        if (!class_exists('FrontHelper')) {
            $this->markTestSkipped('FrontHelper class not found');
        }
        $this->assertTrue(method_exists('FrontHelper', 'grabTagLists'));
    }

    public function testGrabPreparedFrontArchiveHasPsalmSuppress(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/FrontHelper.php');
        $occurrences = substr_count($source, '@psalm-suppress PossiblyUnusedMethod');

        $this->assertGreaterThanOrEqual(3, $occurrences, 'Should have at least 3 @psalm-suppress annotations');
    }

    public function testGrabPreparedFrontArchiveMethodExists(): void
    {
        if (!class_exists('FrontHelper')) {
            $this->markTestSkipped('FrontHelper class not found');
        }
        $this->assertTrue(method_exists('FrontHelper', 'grabPreparedFrontArchive'));
    }

    public function testFrontGalleriesHasPsalmSuppress(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/FrontHelper.php');
        $this->assertStringContainsString('@psalm-suppress PossiblyUnusedMethod', $source);
    }

    public function testGrabPreparedFrontGalleriesMethodExists(): void
    {
        if (!class_exists('FrontHelper')) {
            $this->markTestSkipped('FrontHelper class not found');
        }
        $this->assertTrue(method_exists('FrontHelper', 'grabPreparedFrontGalleries'));
    }
}
