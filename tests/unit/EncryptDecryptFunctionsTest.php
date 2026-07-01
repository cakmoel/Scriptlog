<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class EncryptDecryptFunctionsTest extends TestCase
{
    private string $testKey;
    private string $testData;

    protected function setUp(): void
    {
        $this->testKey = bin2hex(random_bytes(16));
        $this->testData = 'Hello, this is a secret message!';
    }

    public function testCreateEncodedKeyReturnsString(): void
    {
        $this->assertTrue(function_exists('create_encoded_key'));
        $result = create_encoded_key();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testCreateEncodedKeyIsBase64Encoded(): void
    {
        $result = create_encoded_key();
        $decoded = base64_decode($result, true);
        $this->assertNotFalse($decoded);
        $this->assertEquals(32, strlen($decoded));
    }

    public function testEncryptReturnsString(): void
    {
        $encrypted = encrypt($this->testData, $this->testKey);
        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
    }

    public function testEncryptDoesNotReturnPlaintext(): void
    {
        $encrypted = encrypt($this->testData, $this->testKey);
        $this->assertNotEquals($this->testData, $encrypted);
    }

    public function testDecryptReturnsOriginalData(): void
    {
        $encrypted = encrypt($this->testData, $this->testKey);
        $decrypted = decrypt($encrypted, $this->testKey);
        $this->assertEquals($this->testData, $decrypted);
    }

    public function testTryDecryptWithInvalidBase64(): void
    {
        $this->assertTrue(function_exists('_try_decrypt'));
        $result = _try_decrypt('not-valid-base64!!!', $this->testKey, $this->testKey);
        $this->assertFalse($result);
    }

    public function testTryDecryptWithTooShortData(): void
    {
        $short = base64_encode('short');
        $result = _try_decrypt($short, $this->testKey, $this->testKey);
        $this->assertFalse($result);
    }

    public function testTryDecryptWithWrongKeys(): void
    {
        $encrypted = encrypt($this->testData, $this->testKey);
        $wrongKey = bin2hex(random_bytes(16));
        $wrongAes = base64_decode(create_encoded_key());
        $result = _try_decrypt($encrypted, $wrongKey, $wrongAes);
        $this->assertFalse($result);
    }

    public function testDecryptReturnsFalseWithWrongKey(): void
    {
        $encrypted = encrypt($this->testData, $this->testKey);
        $wrongKey = bin2hex(random_bytes(16));
        $result = decrypt($encrypted, $wrongKey);
        $this->assertFalse($result);
    }

    public function testDecryptWithInvalidCiphertext(): void
    {
        $result = decrypt('invalid-data', $this->testKey);
        $this->assertFalse($result);
    }

    public function testEncryptProducesDifferentOutputEachTime(): void
    {
        $encrypted1 = encrypt($this->testData, $this->testKey);
        $encrypted2 = encrypt($this->testData, $this->testKey);
        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    public function testEncryptAndDecryptWithEmptyString(): void
    {
        $encrypted = encrypt('', $this->testKey);
        $decrypted = decrypt($encrypted, $this->testKey);
        $this->assertEquals('', $decrypted);
    }

    public function testEncryptAndDecryptUnicodeText(): void
    {
        $unicode = 'Привет мир! 中文测试 🎉';
        $encrypted = encrypt($unicode, $this->testKey);
        $decrypted = decrypt($encrypted, $this->testKey);
        $this->assertEquals($unicode, $decrypted);
    }

    public function testEncryptAndDecryptLongText(): void
    {
        $longText = str_repeat('A', 10000);
        $encrypted = encrypt($longText, $this->testKey);
        $decrypted = decrypt($encrypted, $this->testKey);
        $this->assertEquals($longText, $decrypted);
    }

    public function testEncryptAndDecryptWithSpecialCharacters(): void
    {
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?~`';
        $encrypted = encrypt($special, $this->testKey);
        $decrypted = decrypt($encrypted, $this->testKey);
        $this->assertEquals($special, $decrypted);
    }

    public function testDecryptWithLegacyHexKey(): void
    {
        $legacyKey = '5d41402abc4b2a76b9719d911017c592';
        $message = 'legacy format message';
        $encrypted = encrypt($message, $legacyKey);
        $decrypted = decrypt($encrypted, $legacyKey);
        $this->assertEquals($message, $decrypted);
    }

    public function testDecryptWithBase64Key(): void
    {
        $b64Key = base64_encode(random_bytes(16));
        $message = 'base64 key message';
        $encrypted = encrypt($message, $b64Key);
        $decrypted = decrypt($encrypted, $b64Key);
        $this->assertEquals($message, $decrypted);
    }

    public function testCreateEncodedKeyDeterministicWithSameConfig(): void
    {
        $result1 = create_encoded_key();
        $result2 = create_encoded_key();
        $this->assertEquals($result1, $result2);
    }

    public function testFunctionExistence(): void
    {
        $this->assertTrue(function_exists('create_encoded_key'));
        $this->assertTrue(function_exists('encrypt'));
        $this->assertTrue(function_exists('decrypt'));
        $this->assertTrue(function_exists('_try_decrypt'));
    }
}
