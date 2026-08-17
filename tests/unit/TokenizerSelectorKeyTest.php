<?php

use PHPUnit\Framework\TestCase;

/**
 * TokenizerSelectorKeyTest
 *
 * Unit tests for the Tokenizer::getSelectorKey() method added to derive
 * a stable 32-byte key for selector encryption from the application key.
 *
 * @category Unit Test
 * @author Blogware Team
 * @license MIT
 */
class TokenizerSelectorKeyTest extends TestCase
{
    private function getTokenizerClass(): string
    {
        return class_exists('Tokenizer') ? 'Tokenizer' : 'Scriptlog\Core\Tokenizer';
    }

    public function testTokenizerClassExists(): void
    {
        $className = $this->getTokenizerClass();
        $this->assertTrue(class_exists($className));
    }

    public function testGetSelectorKeyMethodExists(): void
    {
        $className = $this->getTokenizerClass();
        $this->assertTrue(method_exists($className, 'getSelectorKey'));
    }

    public function testGetSelectorKeyIsStatic(): void
    {
        $className = $this->getTokenizerClass();
        $reflection = new ReflectionMethod($className, 'getSelectorKey');
        $this->assertTrue($reflection->isStatic());
    }

    public function testGetSelectorKeyReturnsString(): void
    {
        if (!function_exists('app_key') || empty(app_key())) {
            $this->markTestSkipped('app_key() not available or empty in test environment');
        }

        $className = $this->getTokenizerClass();
        $key = $className::getSelectorKey();
        $this->assertIsString($key);
    }

    public function testGetSelectorKeyReturns32Bytes(): void
    {
        if (!function_exists('app_key') || empty(app_key())) {
            $this->markTestSkipped('app_key() not available or empty in test environment');
        }

        $className = $this->getTokenizerClass();
        $key = $className::getSelectorKey();
        $this->assertEquals(32, strlen($key));
    }

    public function testGetSelectorKeyIsDeterministic(): void
    {
        if (!function_exists('app_key') || empty(app_key())) {
            $this->markTestSkipped('app_key() not available or empty in test environment');
        }

        $className = $this->getTokenizerClass();
        $key1 = $className::getSelectorKey();
        $key2 = $className::getSelectorKey();
        $this->assertSame($key1, $key2);
    }

    public function testGetSelectorKeyIsBinary(): void
    {
        if (!function_exists('app_key') || empty(app_key())) {
            $this->markTestSkipped('app_key() not available or empty in test environment');
        }

        $className = $this->getTokenizerClass();
        $key = $className::getSelectorKey();
        $this->assertNotEquals(bin2hex($key), $key, 'Key should be raw binary, not hex-encoded');
    }

    public function testGetSelectorKeyDiffersFromAppKey(): void
    {
        if (!function_exists('app_key') || empty(app_key())) {
            $this->markTestSkipped('app_key() not available or empty in test environment');
        }

        $className = $this->getTokenizerClass();
        $selectorKey = $className::getSelectorKey();
        $appKey = app_key();
        $this->assertNotSame($appKey, $selectorKey);
    }

    public function testGetSelectorKeyUsesSha256Hash(): void
    {
        if (!function_exists('app_key') || empty(app_key())) {
            $this->markTestSkipped('app_key() not available or empty in test environment');
        }

        $className = $this->getTokenizerClass();
        $key = $className::getSelectorKey();
        $expected = hash('sha256', app_key(), true);
        $this->assertSame($expected, $key);
    }
}
