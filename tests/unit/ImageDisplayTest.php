<?php
/**
 * Image Display Functions Test
 * 
 * Tests for image utility functions: invoke_frontimg, invoke_responsive_image, 
 * invoke_webp_image, invoke_hero_image, invoke_gallery_image
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ImageDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        // Load all utilities via utility-loader
        require_once __DIR__ . '/../../src/lib/utility-loader.php';
        
        // Initialize database connection for tests that need it
        if (class_exists('Registry') && !Registry::get('dbc') && function_exists('set_test_database_connection')) {
            set_test_database_connection();
        }
    }

    public function testInvokeFrontimgWithEmptyFilename(): void
    {
        if (!function_exists('invoke_frontimg')) {
            $this->markTestSkipped('invoke_frontimg function not found');
        }
        
        $result = invoke_frontimg('');
        $this->assertEquals('', $result);
    }

    public function testInvokeFrontimgWithNophoto(): void
    {
        if (!function_exists('invoke_frontimg')) {
            $this->markTestSkipped('invoke_frontimg function not found');
        }
        
        // This test requires database connection
        if (!class_exists('Registry') || !Registry::get('dbc')) {
            $this->markTestSkipped('Database connection required for this test');
        }
        
        $result = invoke_frontimg('nophoto');
        $this->assertStringContainsString('nophoto', $result);
    }

    public function testInvokeResponsiveImageWithEmptyFilename(): void
    {
        if (!function_exists('invoke_responsive_image')) {
            $this->markTestSkipped('invoke_responsive_image function not found');
        }
        
        $result = invoke_responsive_image('', 'thumbnail', true, 'Test Image');
        $this->assertStringContainsString('via.placeholder.com', $result);
    }

    public function testInvokeResponsiveImageWithNophoto(): void
    {
        if (!function_exists('invoke_responsive_image')) {
            $this->markTestSkipped('invoke_responsive_image function not found');
        }
        
        $result = invoke_responsive_image('nophoto', 'thumbnail', true, 'Test Image');
        $this->assertStringContainsString('via.placeholder.com', $result);
    }

    public function testInvokeResponsiveImageReturnsPictureTag(): void
    {
        if (!function_exists('invoke_responsive_image')) {
            $this->markTestSkipped('invoke_responsive_image function not found');
        }
        
        $result = invoke_responsive_image('test-image.jpg', 'thumbnail', true, 'Test');
        
        if (strpos($result, 'public/files/pictures') !== false || strpos($result, 'via.placeholder') !== false) {
            $this->assertTrue(true);
        } else {
            $this->fail('Expected picture tag or placeholder, got: ' . $result);
        }
    }

    public function testInvokeResponsiveImageWithMediumSize(): void
    {
        if (!function_exists('invoke_responsive_image')) {
            $this->markTestSkipped('invoke_responsive_image function not found');
        }
        
        $result = invoke_responsive_image('test.jpg', 'medium', true, 'Test', 'img-fluid');
        $this->assertStringContainsString('img-fluid', $result);
    }

    public function testInvokeGalleryImageWithEmptyFilename(): void
    {
        if (!function_exists('invoke_gallery_image')) {
            $this->markTestSkipped('invoke_gallery_image function not found');
        }
        
        $result = invoke_gallery_image('', 'Gallery Image');
        $this->assertStringContainsString('loading="lazy"', $result);
    }

    public function testInvokeGalleryImageWithNophoto(): void
    {
        if (!function_exists('invoke_gallery_image')) {
            $this->markTestSkipped('invoke_gallery_image function not found');
        }
        
        $result = invoke_gallery_image('nophoto', 'Gallery Image');
        $this->assertStringContainsString('loading="lazy"', $result);
    }

    public function testInvokeGalleryImageReturnsProperHtml(): void
    {
        if (!function_exists('invoke_gallery_image')) {
            $this->markTestSkipped('invoke_gallery_image function not found');
        }
        
        $result = invoke_gallery_image('test.jpg', 'Gallery');
        
        if (strpos($result, 'public/files/pictures') !== false || strpos($result, 'via.placeholder') !== false) {
            $this->assertTrue(true);
        } else {
            $this->fail('Expected image URL or placeholder, got: ' . $result);
        }
    }

    public function testImageHtmlContainsAltAttribute(): void
    {
        if (!function_exists('invoke_responsive_image')) {
            $this->markTestSkipped('invoke_responsive_image function not found');
        }
        
        $result = invoke_responsive_image('test.jpg', 'thumbnail', true, 'My Test Image');
        $this->assertStringContainsString('alt="My Test Image"', $result);
    }

    public function testImageHtmlContainsClassAttribute(): void
    {
        if (!function_exists('invoke_responsive_image')) {
            $this->markTestSkipped('invoke_responsive_image function not found');
        }
        
        $result = invoke_responsive_image('test.jpg', 'thumbnail', true, 'Test', 'img-fluid');
        $this->assertStringContainsString('class="img-fluid"', $result);
    }
}
