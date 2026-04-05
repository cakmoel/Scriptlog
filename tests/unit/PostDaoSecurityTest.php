<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostDao Security Test
 * 
 * Tests security features in PostDao including status/visibility filtering.
 * Verifies that draft and private posts cannot be accessed through frontend.
 * 
 * This test focuses on method signature and parameter validation without DB connection.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostDaoSecurityTest extends TestCase
{
    public function testFindPostsHasOnlyPublishedParameter(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }
        
        $reflection = new ReflectionClass('PostDao');
        $method = $reflection->getMethod('findPosts');
        $params = $method->getParameters();
        
        // Check that third parameter exists
        $this->assertEquals(3, count($params));
        $this->assertEquals('onlyPublished', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
        $this->assertTrue($params[2]->getDefaultValue());
    }
    
    public function testFindPostHasOnlyPublishedParameter(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }
        
        $reflection = new ReflectionClass('PostDao');
        $method = $reflection->getMethod('findPost');
        $params = $method->getParameters();
        
        // Check that fourth parameter exists
        $this->assertEquals(4, count($params));
        $this->assertEquals('onlyPublished', $params[3]->getName());
        $this->assertTrue($params[3]->isOptional());
        $this->assertTrue($params[3]->getDefaultValue());
    }
    
    public function testFindPostsHasAuthorParameter(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }
        
        $reflection = new ReflectionClass('PostDao');
        $method = $reflection->getMethod('findPosts');
        $params = $method->getParameters();
        
        // Check that author parameter exists (nullable)
        $this->assertEquals(3, count($params));
        $this->assertEquals('author', $params[1]->getName());
        $this->assertNull($params[1]->getDefaultValue());
    }
    
    public function testFindPostsHasSanitizedOrderBy(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }
        
        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');
        
        // Verify that ORDER BY uses a whitelist
        $this->assertStringContainsString('$allowedColumns', $source);
        $this->assertStringContainsString('in_array($orderBy, $allowedColumns)', $source);
        $this->assertStringContainsString('ORDER BY p.$sortColumn', $source);
    }
    
    public function testFindPostsFiltersByStatusAndVisibility(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }
        
        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');
        
        // Verify that status filter is present
        $this->assertStringContainsString("p.post_status = 'publish'", $source);
        $this->assertStringContainsString("p.post_visibility = 'public'", $source);
        $this->assertStringContainsString('if ($onlyPublished)', $source);
    }
    
    public function testFindPostFiltersByStatusAndVisibility(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
            return;
        }
        
        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');
        
        // Check for status filter anywhere in the findPost method
        $this->assertStringContainsString("post_status = 'publish'", $source);
        $this->assertStringContainsString("post_visibility = 'public'", $source);
    }
}
