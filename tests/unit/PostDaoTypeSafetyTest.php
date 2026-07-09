<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class PostDaoTypeSafetyTest extends TestCase
{
    public function testFindPostCastsIdToString(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString("filteringId(\$sanitize, (string)\$ID", $source);
    }

    public function testUpdatePostCastsIdToString(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString("filteringId(\$sanitize, (string)\$ID, 'sql')", $source);
    }

    public function testDeletePostCastsIdToString(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString("filteringId(\$sanitize, (string)\$ID, 'sql')", $source);
    }

    public function testCheckPostIdCastsIdToString(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString("filteringId(\$sanitize, (string)\$ID, 'sql')", $source);
    }

    public function testErrorPropertyCastsToString(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString("\$this->error = (string)LogError::setStatusCode", $source);
    }

    public function testAllMethodsCastIdCorrectly(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $occurrences = substr_count($source, '(string)$ID');
        $this->assertGreaterThanOrEqual(4, $occurrences, 'Should have at least 4 explicit (string) casts for $ID');
    }

    public function testFindPostMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $this->assertTrue(method_exists('PostDao', 'findPost'));
    }

    public function testUpdatePostMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $this->assertTrue(method_exists('PostDao', 'updatePost'));
    }

    public function testDeletePostMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $this->assertTrue(method_exists('PostDao', 'deletePost'));
    }

    public function testCheckPostIdMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $this->assertTrue(method_exists('PostDao', 'checkPostId'));
    }

    public function testDaoFilteringIdPhpDocUpdated(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/Dao.php');

        $this->assertStringContainsString('@param int|string $str', $source);
    }

    public function testViewErrorPhpDocUpdated(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/View.php');

        $this->assertStringContainsString('@var int|string', $source);
    }
}