<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class DistillPostRequestTest extends TestCase
{
    public function testDistillPostRequestFunctionExists(): void
    {
        $this->assertTrue(function_exists('distill_post_request'));
    }

    public function testUsesFilterInputArray(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/distill-post-request.php');
        $this->assertStringContainsString('filter_input_array', $source);
    }

    public function testBuildsCleanFiltersFromRefine(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/distill-post-request.php');

        $this->assertStringContainsString('$cleanFilters', $source);
        $this->assertStringContainsString('foreach ($refine as $key => $value)', $source);
    }

    public function testValidatesFilterIds(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/distill-post-request.php');

        $this->assertStringContainsString('$validFilterIds', $source);
        $this->assertStringContainsString('filter_id', $source);
        $this->assertStringContainsString('filter_list', $source);
        $this->assertStringContainsString('isset($validFilterIds[$value])', $source);
    }

    public function testPreservesArrayFilterConfigs(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/distill-post-request.php');

        $this->assertStringContainsString('is_array($value)', $source);
    }

    public function testFallsBackToUnsafeRawForInvalidFilters(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/distill-post-request.php');

        $this->assertStringContainsString("FILTER_UNSAFE_RAW", $source);
    }

    public function testNonArrayInputThrowsError(): void
    {
        $this->expectException(\Exception::class);
        distill_post_request('not an array');
    }

    public function testFunctionIsArrayOnly(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/distill-post-request.php');

        $this->assertStringContainsString('if (is_array($refine))', $source);
    }

    public function testUsesStaticCacheForFilterIds(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/distill-post-request.php');

        $this->assertStringContainsString('static $validFilterIds', $source);
        $this->assertStringContainsString('null', $source);
    }
}
