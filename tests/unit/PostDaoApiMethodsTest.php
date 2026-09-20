<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostDao API Methods Test
 *
 * Tests the new API-focused methods in PostDao.
 * Verifies method signatures, SQL structure, and security patterns.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostDaoApiMethodsTest extends TestCase
{
    public function testFindPublishedPostsPaginatedExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $reflection = new ReflectionClass('PostDao');
        $this->assertTrue($reflection->hasMethod('findPublishedPostsPaginated'));
    }

    public function testFindPublishedPostsPaginatedParameters(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $reflection = new ReflectionClass('PostDao');
        $method = $reflection->getMethod('findPublishedPostsPaginated');
        $params = $method->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertEquals('limit', $params[0]->getName());
        $this->assertEquals('offset', $params[1]->getName());
        $this->assertEquals('sortBy', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
        $this->assertEquals('ID', $params[2]->getDefaultValue());
        $this->assertEquals('sortOrder', $params[3]->getName());
        $this->assertTrue($params[3]->isOptional());
        $this->assertEquals('DESC', $params[3]->getDefaultValue());
    }

    public function testCountPublishedPostsExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $reflection = new ReflectionClass('PostDao');
        $this->assertTrue($reflection->hasMethod('countPublishedPosts'));
    }

    public function testFindPublishedPostByIdExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $reflection = new ReflectionClass('PostDao');
        $this->assertTrue($reflection->hasMethod('findPublishedPostById'));
    }

    public function testFindPublishedPostsPaginatedHasStatusFilter(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        $this->assertStringContainsString("p.post_status = 'publish'", $source);
        $this->assertStringContainsString("p.post_visibility = 'public'", $source);
        $this->assertStringContainsString("p.post_type = 'blog'", $source);
    }

    public function testFindPublishedPostsPaginatedHasLimitOffset(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        $this->assertStringContainsString('LIMIT ? OFFSET ?', $source);
    }

    public function testFindPublishedPostsPaginatedHasSortWhitelist(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        $this->assertStringContainsString("['ID', 'post_date', 'post_title', 'post_modified']", $source);
        $this->assertStringContainsString('in_array($sortBy, $allowedColumns)', $source);
    }

    public function testCountPublishedPostsUsesCountStar(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        $this->assertStringContainsString('COUNT(*)', $source);
    }

    public function testFindPublishedPostByIdHasVisibilityFilter(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }

        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        $this->assertStringContainsString("p.post_status = 'publish'", $source);
        $this->assertStringContainsString("p.post_visibility = 'public'", $source);
    }
}
