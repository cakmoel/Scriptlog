<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\Tokenizer;

class TokenizerTest extends TestCase
{
    public function testGetSelectorKeyReturnsString(): void
    {
        $key = Tokenizer::getSelectorKey();
        $this->assertIsString($key, 'getSelectorKey should return a string');
        $this->assertNotEmpty($key, 'getSelectorKey should return non-empty string');
    }

    public function testGetSelectorKeyReturns32Bytes(): void
    {
        $key = Tokenizer::getSelectorKey();
        $this->assertEquals(32, strlen($key),
            'getSelectorKey should return exactly 32 bytes (SHA-256 output)');
    }

    public function testGetSelectorKeyIsDeterministic(): void
    {
        $key1 = Tokenizer::getSelectorKey();
        $key2 = Tokenizer::getSelectorKey();
        $key3 = Tokenizer::getSelectorKey();

        $this->assertEquals($key1, $key2,
            'getSelectorKey should return same value on second call');
        $this->assertEquals($key2, $key3,
            'getSelectorKey should return same value on third call');
    }

    public function testSetRandomPasswordProtectedAndIsPasswordValid(): void
    {
        $password = 'test_password_123!@#';

        $hash = Tokenizer::setRandomPasswordProtected($password);

        $this->assertIsString($hash, 'setRandomPasswordProtected should return a string');
        $this->assertNotEmpty($hash, 'setRandomPasswordProtected should return non-empty string');

        $this->assertTrue(
            Tokenizer::isPasswordValid($password, $hash),
            'isPasswordValid should return true for correct password'
        );

        $this->assertFalse(
            Tokenizer::isPasswordValid('wrong_password', $hash),
            'isPasswordValid should return false for incorrect password'
        );
    }

    public function testSetRandomSelectorProtectedAndIsSelectorValid(): void
    {
        $selector = 'test_selector_token_42';
        $key = Tokenizer::getSelectorKey();

        $encrypted = Tokenizer::setRandomSelectorProtected($selector, $key);

        $this->assertIsString($encrypted,
            'setRandomSelectorProtected should return a string (encrypted + bcrypt hash)');

        $this->assertTrue(
            Tokenizer::isSelectorValid($encrypted, $selector, $key),
            'isSelectorValid should return true for correct selector and key'
        );

        $this->assertFalse(
            Tokenizer::isSelectorValid($encrypted, 'wrong_selector', $key),
            'isSelectorValid should return false for incorrect selector'
        );
    }

    public function testIsSelectorValidWithWrongKeyReturnsFalse(): void
    {
        $selector = 'another_test_selector';
        $correctKey = Tokenizer::getSelectorKey();
        $wrongKey = hash('sha256', 'different-key-material', true);

        $encrypted = Tokenizer::setRandomSelectorProtected($selector, $correctKey);

        $this->assertFalse(
            Tokenizer::isSelectorValid($encrypted, $selector, $wrongKey),
            'isSelectorValid should return false when decrypting with wrong key'
        );
    }

    public function testSetRandomSelectorProtectedIsNonDeterministic(): void
    {
        $selector = 'determinism_test';
        $key = Tokenizer::getSelectorKey();

        $result1 = Tokenizer::setRandomSelectorProtected($selector, $key);
        $result2 = Tokenizer::setRandomSelectorProtected($selector, $key);

        $this->assertNotEquals($result1, $result2,
            'setRandomSelectorProtected should produce different output each call ' .
            '(bcrypt random salt inside the BlockCipher envelope)');
    }

    public function testCreateTokenReturnsExpectedLength(): void
    {
        $lengths = [8, 16, 32, 64, 128];
        foreach ($lengths as $len) {
            $token = Tokenizer::createToken($len);
            $this->assertEquals($len, strlen($token),
                "createToken($len) should return a string of length $len");
        }
    }
}
