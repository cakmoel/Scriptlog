<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ServiceClassesExistenceTest extends TestCase
{
    
    public function testPostServiceClassExists(): void
    {
        $this->assertTrue(class_exists('PostService'));
    }
    
    public function testUserServiceClassExists(): void
    {
        $this->assertTrue(class_exists('UserService'));
    }
    
    public function testCommentServiceClassExists(): void
    {
        $this->assertTrue(class_exists('CommentService'));
    }
    
    public function testTopicServiceClassExists(): void
    {
        $this->assertTrue(class_exists('TopicService'));
    }
    
    public function testMediaServiceClassExists(): void
    {
        $this->assertTrue(class_exists('MediaService'));
    }
    
    public function testPageServiceClassExists(): void
    {
        $this->assertTrue(class_exists('PageService'));
    }
    
    public function testMenuServiceClassExists(): void
    {
        $this->assertTrue(class_exists('MenuService'));
    }
    
    public function testPluginServiceClassExists(): void
    {
        $this->assertTrue(class_exists('PluginService'));
    }
    
    public function testThemeServiceClassExists(): void
    {
        $this->assertTrue(class_exists('ThemeService'));
    }
    
    public function testReplyServiceClassExists(): void
    {
        $this->assertTrue(class_exists('ReplyService'));
    }
    
    public function testConfigurationServiceClassExists(): void
    {
        $this->assertTrue(class_exists('ConfigurationService'));
    }
    
    public function testConsentServiceClassExists(): void
    {
        $this->assertTrue(class_exists('ConsentService'));
    }
}
