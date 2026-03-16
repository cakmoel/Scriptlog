<?php
/**
 * Core Classes Test
 * 
 * Tests for Core utility classes
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class CoreClassesTest extends TestCase
{
    public function testSanitizeClassExists(): void
    {
        $this->assertTrue(class_exists('Sanitize'));
    }
    
    public function testFormValidatorClassExists(): void
    {
        $this->assertTrue(class_exists('FormValidator'));
    }
    
    public function testPaginatorClassExists(): void
    {
        $this->assertTrue(class_exists('Paginator'));
    }
    
    public function testViewClassExists(): void
    {
        $this->assertTrue(class_exists('View'));
    }
    
    public function testDispatcherClassExists(): void
    {
        $this->assertTrue(class_exists('Dispatcher'));
    }
    
    public function testAuthenticationClassExists(): void
    {
        $this->assertTrue(class_exists('Authentication'));
    }
    
    public function testSessionMakerClassExists(): void
    {
        $this->assertTrue(class_exists('SessionMaker'));
    }
    
    public function testDbFactoryClassExists(): void
    {
        $this->assertTrue(class_exists('DbFactory'));
    }
    
    public function testDaoClassExists(): void
    {
        $this->assertTrue(class_exists('Dao'));
    }
    
    public function testDbInterfaceExists(): void
    {
        $this->assertTrue(interface_exists('DbInterface'));
    }
    
    public function testRegistryClassExists(): void
    {
        $this->assertTrue(class_exists('Registry'));
    }
    
    public function testHtmlClassExists(): void
    {
        $this->assertTrue(class_exists('Html'));
    }
}
