<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class DaoClassesExistenceTest extends TestCase
{
    
    public function testPostDaoClassExists(): void
    {
        $this->assertTrue(class_exists('PostDao'));
    }
    
    public function testUserDaoClassExists(): void
    {
        $this->assertTrue(class_exists('UserDao'));
    }
    
    public function testCommentDaoClassExists(): void
    {
        $this->assertTrue(class_exists('CommentDao'));
    }
    
    public function testTopicDaoClassExists(): void
    {
        $this->assertTrue(class_exists('TopicDao'));
    }
    
    public function testMediaDaoClassExists(): void
    {
        $this->assertTrue(class_exists('MediaDao'));
    }
    
    public function testPageDaoClassExists(): void
    {
        $this->assertTrue(class_exists('PageDao'));
    }
    
    public function testMenuDaoClassExists(): void
    {
        $this->assertTrue(class_exists('MenuDao'));
    }
    
    public function testPluginDaoClassExists(): void
    {
        $this->assertTrue(class_exists('PluginDao'));
    }
    
    public function testThemeDaoClassExists(): void
    {
        $this->assertTrue(class_exists('ThemeDao'));
    }
    
    public function testConfigurationDaoClassExists(): void
    {
        $this->assertTrue(class_exists('ConfigurationDao'));
    }
    
    public function testReplyDaoClassExists(): void
    {
        $this->assertTrue(class_exists('ReplyDao'));
    }
    
    public function testUserTokenDaoClassExists(): void
    {
        $this->assertTrue(class_exists('UserTokenDao'));
    }
    
    public function testPostTopicDaoClassExists(): void
    {
        $this->assertTrue(class_exists('PostTopicDao'));
    }
}
