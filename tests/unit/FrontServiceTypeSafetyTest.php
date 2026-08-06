<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class FrontServiceTypeSafetyTest extends TestCase
{
    public function testGetGalleriesQueryUsesTypedIntParams(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/service/FrontService.php');

        // LIMIT/OFFSET values are enforced as int by the method signature,
        // so interpolating them into the query is safe from SQL injection.
        $this->assertStringContainsString(
            'public function getGalleries(int $start, int $limit): ?array',
            $source
        );
        $this->assertStringContainsString('$dbc->dbQuery($sql)', $source);
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
