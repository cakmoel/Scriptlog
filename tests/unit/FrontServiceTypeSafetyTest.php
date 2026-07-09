<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class FrontServiceTypeSafetyTest extends TestCase
{
    public function testGetGalleriesQueryUsesNonCastedParams(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/service/FrontService.php');

        $this->assertStringContainsString('$dbc->dbQuery($sql, [$start, $limit])', $source);
        $this->assertStringNotContainsString('(int)$start, (int)$limit', $source);
    }

    public function testGetGalleriesMethodExists(): void
    {
        if (!class_exists('FrontService')) {
            $this->markTestSkipped('FrontService class not found');
        }
        $this->assertTrue(method_exists('FrontService', 'getGalleries'));
    }

    public function testGetGalleriesReturnsNullOnFailedQuery(): void
    {
        if (!class_exists('FrontService')) {
            $this->markTestSkipped('FrontService class not found');
        }

        $source = file_get_contents(__DIR__ . '/../../src/lib/service/FrontService.php');
        $this->assertStringContainsString('return null', $source);
    }
}
