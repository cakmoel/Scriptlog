<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostDaoSchedulingTest
 *
 * Structural unit tests for the scheduled-posting methods added to PostDao:
 * publishDueScheduledPosts() and nextScheduledPostDate().
 *
 * No database connection is required; methods are verified via reflection
 * and source inspection (same pattern as PostDaoSecurityTest).
 *
 * @category Tests
 * @version  1.0
 */

use PHPUnit\Framework\TestCase;

class PostDaoSchedulingTest extends TestCase
{
    public function testPublishDueScheduledPostsMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $reflection = new ReflectionClass('PostDao');
        $method = $reflection->getMethod('publishDueScheduledPosts');

        $this->assertEquals('publishDueScheduledPosts', $method->getName());
        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('now', $method->getParameters()[0]->getName());
    }

    public function testNextScheduledPostDateMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $reflection = new ReflectionClass('PostDao');
        $method = $reflection->getMethod('nextScheduledPostDate');

        $this->assertEquals('nextScheduledPostDate', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
    }

    public function testPublishDueScheduledPostsRunsInTransaction(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString('function publishDueScheduledPosts', $source);
        $this->assertStringContainsString('runInTransaction', $source);
        $this->assertStringContainsString('publishDueScheduledPosts', $source);
    }

    public function testPublishDueScheduledPostsPromotesScheduledRows(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString("post_status = 'scheduled'", $source);
        $this->assertStringContainsString("p.post_status = 'publish'", $source);
        $this->assertStringContainsString('m.media_access = \'public\'', $source);
    }

    public function testPublishDueScheduledPostsFiltersOnPostDate(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString('p.post_date IS NOT NULL', $source);
        $this->assertStringContainsString('p.post_date <= ?', $source);
    }

    public function testPublishDueScheduledPostsClearsPageCache(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString('page_cache_clear', $source);
    }

    public function testNextScheduledPostDateQueriesMinimumDate(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString('MIN(post_date)', $source);
        $this->assertStringContainsString("post_status = 'scheduled'", $source);
    }

    public function testNextScheduledPostDateReturnsNullForEmptyResult(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/PostDao.php');

        $this->assertStringContainsString('return ($value === false || $value === null || $value === \'\') ? null : (string)$value;', $source);
    }
}
