<?php

use PHPUnit\Framework\TestCase;
use Defuse\Crypto\Key;

// Ensure ScriptlogCryptonize class is loaded
if (!class_exists('ScriptlogCryptonize')) {
    require_once dirname(__DIR__, 2) . '/src/lib/core/ScriptlogCryptonize.php';
}

// Ensure exception class exists
if (!class_exists('ScriptlogCryptonizeException')) {
    class ScriptlogCryptonizeException extends Exception {}
}

class ScriptlogCryptonizeTest extends TestCase
{
    private string $validMasterKey;
    private string $shortKey;
    private string $testPlaintext;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists('ScriptlogCryptonize')) {
            $this->markTestSkipped('ScriptlogCryptonize class not available');
        }

        $this->validMasterKey = ScriptlogCryptonize::generateSecretKey();
        $this->shortKey = random_bytes(32);
        $this->testPlaintext = "This is a secret message that needs encryption! 🚀";
    }

    // ============================================================
    // generateSecretKey() Tests
    // ============================================================

    public function testGenerateSecretKeyReturns64Bytes(): void
    {
        $key = ScriptlogCryptonize::generateSecretKey();
        $this->assertIsString($key);
        $this->assertEquals(64, strlen($key));
    }

    public function testGenerateSecretKeyReturnsDifferentKeys(): void
    {
        $key1 = ScriptlogCryptonize::generateSecretKey();
        $key2 = ScriptlogCryptonize::generateSecretKey();
        $this->assertNotEquals($key1, $key2);
    }

    // ============================================================
    // encryptAES() and decryptAES() Tests
    // ============================================================

    public function testEncryptAndDecryptWithValidKey(): void
    {
        $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->validMasterKey);
        $decrypted = ScriptlogCryptonize::decryptAES($encrypted, $this->validMasterKey);
        $this->assertEquals($this->testPlaintext, $decrypted);
    }

    public function testEncryptAESReturnsBase64String(): void
    {
        $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->validMasterKey);
        $this->assertIsString($encrypted);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $encrypted);
    }

    public function testEncryptDecryptEmptyString(): void
    {
        $emptyString = '';
        $encrypted = ScriptlogCryptonize::encryptAES($emptyString, $this->validMasterKey);
        $decrypted = ScriptlogCryptonize::decryptAES($encrypted, $this->validMasterKey);
        $this->assertEquals($emptyString, $decrypted);
    }

    public function testEncryptDecryptUnicodeCharacters(): void
    {
        $unicodeText = "Привет мир! こんにちは世界！ 🌍🌎🌏 中文测试 🎉";
        $encrypted = ScriptlogCryptonize::encryptAES($unicodeText, $this->validMasterKey);
        $decrypted = ScriptlogCryptonize::decryptAES($encrypted, $this->validMasterKey);
        $this->assertEquals($unicodeText, $decrypted);
    }

    public function testEncryptDecryptBinaryData(): void
    {
        $binaryData = random_bytes(256);
        $encrypted = ScriptlogCryptonize::encryptAES($binaryData, $this->validMasterKey);
        $decrypted = ScriptlogCryptonize::decryptAES($encrypted, $this->validMasterKey);
        $this->assertEquals($binaryData, $decrypted);
    }

    public function testDecryptFailsWithWrongKey(): void
    {
        $this->expectException(ScriptlogCryptonizeException::class);

        $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->validMasterKey);
        $wrongKey = ScriptlogCryptonize::generateSecretKey();

        ScriptlogCryptonize::decryptAES($encrypted, $wrongKey);
    }

    public function testDecryptFailsWithTamperedCiphertext(): void
    {
        $this->expectException(ScriptlogCryptonizeException::class);
        $this->expectExceptionMessage('HMAC verification failed');

        $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->validMasterKey);
        $tampered = $encrypted;
        $tampered[5] = $tampered[5] === 'A' ? 'B' : 'A';

        ScriptlogCryptonize::decryptAES($tampered, $this->validMasterKey);
    }

    public function testHmacPreventsIvTampering(): void
    {
        $this->expectException(ScriptlogCryptonizeException::class);
        $this->expectExceptionMessage('HMAC verification failed');

        $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->validMasterKey);
        $decoded = base64_decode($encrypted);

        if (strlen($decoded) > 48) {
            $decoded[40] = chr(ord($decoded[40]) ^ 0xFF);
            $tampered = base64_encode($decoded);
            ScriptlogCryptonize::decryptAES($tampered, $this->validMasterKey);
        } else {
            $this->markTestSkipped('Ciphertext too short for IV tampering test');
        }
    }

    public function testHmacPreventsCiphertextTampering(): void
    {
        $this->expectException(ScriptlogCryptonizeException::class);
        $this->expectExceptionMessage('HMAC verification failed');

        $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->validMasterKey);
        $decoded = base64_decode($encrypted);

        if (strlen($decoded) > 49) {
            $decoded[50] = chr(ord($decoded[50]) ^ 0xFF);
            $tampered = base64_encode($decoded);
            ScriptlogCryptonize::decryptAES($tampered, $this->validMasterKey);
        } else {
            $this->markTestSkipped('Ciphertext too short for tampering test');
        }
    }

    public function testDecryptFailsWithInvalidBase64(): void
    {
        $this->expectException(ScriptlogCryptonizeException::class);
        $this->expectExceptionMessage('base64 decoding failed');

        ScriptlogCryptonize::decryptAES('not-valid-base64!!!', $this->validMasterKey);
    }

    public function testDecryptFailsWithTooShortCiphertext(): void
    {
        $this->expectException(ScriptlogCryptonizeException::class);
        $this->expectExceptionMessage('too short');

        // 48 bytes = 32 (HMAC) + 16 (IV), need at least 49 for 1 byte of ciphertext
        $tooShort = base64_encode(str_repeat('x', 48));
        ScriptlogCryptonize::decryptAES($tooShort, $this->validMasterKey);
    }

    public function testBackwardCompatibilityWith32ByteKey(): void
    {
        try {
            $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->shortKey);
            $decrypted = ScriptlogCryptonize::decryptAES($encrypted, $this->shortKey);
            $this->assertEquals($this->testPlaintext, $decrypted);
        } catch (ScriptlogCryptonizeException $e) {
            $this->fail("32-byte key should be supported but got: " . $e->getMessage());
        }
    }

    public function testDeriveKeysThrowsExceptionForTooShortKey(): void
    {
        $this->expectException(ScriptlogCryptonizeException::class);
        $this->expectExceptionMessage('Invalid key length');

        $shortKey = random_bytes(16); // Too short

        // Use reflection to call private static method
        $ref = new ReflectionClass('ScriptlogCryptonize');
        $method = $ref->getMethod('deriveKeys');
        $method->setAccessible(true);
        $method->invoke(null, $shortKey);
    }

    // ============================================================
    // cipherMessage() and decipherMessage() Tests (Laminas Crypt)
    // ============================================================

    public function testCipherAndDecipherMessage(): void
    {
        $key = ScriptlogCryptonize::generateSecretKey();
        $message = "Secret message for Laminas Crypt";

        $encrypted = ScriptlogCryptonize::cipherMessage($message, $key);
        $this->assertIsString($encrypted);
        $this->assertNotEquals($message, $encrypted);

        // Laminas decrypt may return false or throw on failure
        $result = ScriptlogCryptonize::decipherMessage($encrypted, $key);
        $this->assertEquals($message, $result);
    }

    public function testDecipherMessageFailsWithWrongKey(): void
    {
        $key1 = ScriptlogCryptonize::generateSecretKey();
        $key2 = ScriptlogCryptonize::generateSecretKey();
        $message = "Secret message";

        $encrypted = ScriptlogCryptonize::cipherMessage($message, $key1);

        // Laminas Crypt may throw exception OR return false on failure
        try {
            $result = ScriptlogCryptonize::decipherMessage($encrypted, $key2);
            // If we get here without exception, result should not equal original message
            $this->assertNotEquals($message, $result, "Decryption with wrong key should not succeed");
        } catch (Exception $e) {
            // Exception is acceptable - this is expected behavior
            $this->assertTrue(true, "Exception thrown as expected");
        }
    }

    // ============================================================
    // scriptlogCipher() and scriptlogDecipher() Tests (Defuse Crypto)
    // ============================================================

    public function testScriptlogCipherAndDecipher(): void
    {
        if (!class_exists('Defuse\Crypto\Crypto')) {
            $this->markTestSkipped('Defuse\Crypto\Crypto class not available');
        }

        $key = ScriptlogCryptonize::scriptlogCipherKey();
        $message = "Secret message for Defuse Crypto";

        $encrypted = ScriptlogCryptonize::scriptlogCipher($message, $key);
        $this->assertIsString($encrypted);
        $this->assertNotEquals($message, $encrypted);

        $decrypted = ScriptlogCryptonize::scriptlogDecipher($encrypted, $key);
        $this->assertEquals($message, $decrypted);
    }

    public function testScriptlogCipherKeyReturnsValidKey(): void
    {
        if (!class_exists('Defuse\Crypto\Crypto')) {
            $this->markTestSkipped('Defuse\Crypto\Crypto class not available');
        }

        $key = ScriptlogCryptonize::scriptlogCipherKey();
        $this->assertInstanceOf(Key::class, $key);

        // Key should be usable for encryption
        $encrypted = \Defuse\Crypto\Crypto::encrypt("test", $key);
        $decrypted = \Defuse\Crypto\Crypto::decrypt($encrypted, $key);
        $this->assertEquals("test", $decrypted);
    }

    // ============================================================
    // generateRandomBytes() Tests (Private static method via reflection)
    // ============================================================

    public function testGenerateRandomBytesReturnsCorrectLength(): void
    {
        $ref = new ReflectionClass('ScriptlogCryptonize');
        $method = $ref->getMethod('generateRandomBytes');
        $method->setAccessible(true);

        $bytes16 = $method->invoke(null, 16);
        $bytes32 = $method->invoke(null, 32);
        $bytes64 = $method->invoke(null, 64);

        $this->assertEquals(16, strlen($bytes16));
        $this->assertEquals(32, strlen($bytes32));
        $this->assertEquals(64, strlen($bytes64));
    }

    public function testGenerateRandomBytesReturnsDifferentValues(): void
    {
        $ref = new ReflectionClass('ScriptlogCryptonize');
        $method = $ref->getMethod('generateRandomBytes');
        $method->setAccessible(true);

        $bytes1 = $method->invoke(null, 32);
        $bytes2 = $method->invoke(null, 32);

        $this->assertNotEquals($bytes1, $bytes2);
    }

    // ============================================================
    // resolvePath() Tests (Private static method via reflection)
    // ============================================================

    public function testResolvePathWithAbsolutePath(): void
    {
        $ref = new ReflectionClass('ScriptlogCryptonize');
        $method = $ref->getMethod('resolvePath');
        $method->setAccessible(true);

        $absolutePath = '/tmp/test_key.php';
        $result = $method->invoke(null, $absolutePath);
        $this->assertEquals($absolutePath, $result);
    }

    public function testResolvePathWithRelativePath(): void
    {
        $ref = new ReflectionClass('ScriptlogCryptonize');
        $method = $ref->getMethod('resolvePath');
        $method->setAccessible(true);

        // Relative paths should be resolved from app root
        $relativePath = 'config.php';
        $result = $method->invoke(null, $relativePath);

        // Should return either the resolved realpath or the constructed path
        $this->assertIsString($result);
        $this->assertStringContainsString('config.php', $result);
    }

    // ============================================================
    // Performance Tests
    // ============================================================

    public function testEncryptionPerformance(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $encrypted = ScriptlogCryptonize::encryptAES($this->testPlaintext, $this->validMasterKey);
            ScriptlogCryptonize::decryptAES($encrypted, $this->validMasterKey);
        }

        $duration = microtime(true) - $startTime;
        $this->assertLessThan(5.0, $duration, "Encryption/decryption took too long: {$duration}s");
    }

    // ============================================================
    // Constants Tests
    // ============================================================

    public function testConstantsAreDefined(): void
    {
        $this->assertEquals('AES-256-CBC', ScriptlogCryptonize::METHOD);
        $this->assertEquals(32, ScriptlogCryptonize::ENCRYPTION_KEY_LEN);
        $this->assertEquals(32, ScriptlogCryptonize::HMAC_KEY_LEN);
        $this->assertEquals(64, ScriptlogCryptonize::TOTAL_KEY_LEN);
    }
}
