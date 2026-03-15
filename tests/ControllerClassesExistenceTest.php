<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ControllerClassesExistenceTest extends TestCase
{
    
    public function testPostControllerClassExists(): void
    {
        $this->assertTrue(class_exists('PostController'));
    }
    
    public function testUserControllerClassExists(): void
    {
        $this->assertTrue(class_exists('UserController'));
    }
    
    public function testCommentControllerClassExists(): void
    {
        $this->assertTrue(class_exists('CommentController'));
    }
    
    public function testTopicControllerClassExists(): void
    {
        $this->assertTrue(class_exists('TopicController'));
    }
    
    public function testMediaControllerClassExists(): void
    {
        $this->assertTrue(class_exists('MediaController'));
    }
    
    public function testPageControllerClassExists(): void
    {
        $this->assertTrue(class_exists('PageController'));
    }
    
    public function testMenuControllerClassExists(): void
    {
        $this->assertTrue(class_exists('MenuController'));
    }
    
    public function testPluginControllerClassExists(): void
    {
        $this->assertTrue(class_exists('PluginController'));
    }
    
    public function testThemeControllerClassExists(): void
    {
        $this->assertTrue(class_exists('ThemeController'));
    }
    
    public function testReplyControllerClassExists(): void
    {
        $this->assertTrue(class_exists('ReplyController'));
    }
    
    public function testConfigurationControllerClassExists(): void
    {
        $this->assertTrue(class_exists('ConfigurationController'));
    }
}
