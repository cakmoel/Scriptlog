<?php
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class ScriptlogCryptonize
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */

use Laminas\Crypt\BlockCipher;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class ScriptlogCryptonize
{
    const METHOD = 'AES-256-CBC';

    /**
     * Generate a secure secret key
     *
     * @return string
     */
    public static function generateSecretKey(): string
    {
        return self::generateRandomBytes(32); // 32 bytes for AES-256
    }

    /**
     * Encrypt a message using Laminas\Crypt
     *
     * @param string $message
     * @param string $key
     * @return string
     */
    public static function cipherMessage(string $message, string $key): string
    {
        $cipher = BlockCipher::factory('openssl', ['algo' => 'aes']);
        $cipher->setKey($key);
        return $cipher->encrypt($message);
    }

    /**
     * Decrypt a message using Laminas\Crypt
     *
     * @param string $ciphertext
     * @param string $key
     * @return string
     */
    public static function decipherMessage(string $ciphertext, string $key): string
    {
        $cipher = BlockCipher::factory('openssl', ['algo' => 'aes']);
        $cipher->setKey($key);
        return $cipher->decrypt($ciphertext);
    }

    /**
     * Encrypt a message using Defuse\Crypto
     *
     * @param string $message
     * @param Key $key
     * @return string
     */
    public static function scriptlogCipher(string $message, Key $key): string
    {
        return Crypto::encrypt($message, $key);
    }

    /**
     * Decrypt a message using Defuse\Crypto
     *
     * @param string $ciphertext
     * @param Key $key // <<< CHANGE THIS
     * @return string
     */
    public static function scriptlogDecipher(string $ciphertext, Key $key): string
    {
        return Crypto::decrypt($ciphertext, $key);
    }

    /**
     * Encrypt data using AES-256-CBC with HMAC authentication
     *
     * @param string $plaintext
     * @param string $key
     * @return string
     */
    public static function encryptAES(string $plaintext, string $key): string
    {
        // Generate a random IV
        $iv = self::generateRandomBytes(16);

        // Encrypt the plaintext
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::METHOD,
            mb_substr($key, 0, 32, '8bit'),
            OPENSSL_RAW_DATA,
            $iv
        );

        // Generate HMAC for authentication
        $hmac = hash_hmac(
            'sha256',
            $iv . $ciphertext,
            mb_substr($key, 32, null, '8bit'),
            true
        );

        return $hmac . $iv . $ciphertext;
    }

    /**
     * Decrypt data using AES-256-CBC with HMAC authentication
     *
     * @param string $ciphertext
     * @param string $key
     * @return string
     * @throws ScriptlogCryptonizeException
     */
    public static function decryptAES(string $ciphertext, string $key): string
    {
        try {
            // Validate key length
            if (strlen($key) < 64) {
                throw new ScriptlogCryptonizeException("Invalid key length: expected 64 bytes, got " . strlen($key));
            }

            // Validate ciphertext length (minimum: 32 HMAC + 16 IV + 1 cipher)
            if (strlen($ciphertext) < 49) {
                throw new ScriptlogCryptonizeException("Invalid ciphertext: too short");
            }

            // Extract HMAC, IV, and ciphertext
            $hmac = mb_substr($ciphertext, 0, 32, '8bit');
            $iv = mb_substr($ciphertext, 32, 16, '8bit');
            $cipher = mb_substr($ciphertext, 48, null, '8bit');

            // Verify HMAC
            $hmacNew = hash_hmac(
                'sha256',
                $iv . $cipher,
                mb_substr($key, 32, null, '8bit'),
                true
            );

            if (!hash_equals($hmac, $hmacNew)) {
                throw new ScriptlogCryptonizeException("Invalid ciphertext: HMAC verification failed");
            }

            // Decrypt the ciphertext
            $plaintext = openssl_decrypt(
                $cipher,
                self::METHOD,
                mb_substr($key, 0, 32, '8bit'),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($plaintext === false) {
                throw new ScriptlogCryptonizeException("Decryption failed");
            }

            return $plaintext;
        } catch (ScriptlogCryptonizeException $e) {
            LogError::setStatusCode(http_response_code());
            LogError::newMessage($e);
            LogError::customErrorMessage('admin');
            throw $e; // Re-throw the exception for further handling
        }
    }

    /**
     * Load or generate a Defuse\Crypto key
     *
     * @return Key
     */
    public static function scriptlogCipherKey(): Key
    {
        $keyFile = __DIR__ . '/../../lib/utility/.lts/lts.txt';

        if (file_exists($keyFile)) {
            $keyAscii = file_get_contents($keyFile);
        } else {
            $keyObject = Key::createNewRandomKey();
            $keyAscii = $keyObject->saveToAsciiSafeString();
            file_put_contents($keyFile, $keyAscii); // Save the key for future use
        }

        return Key::loadFromAsciiSafeString($keyAscii);
    }

    /**
     * Generate random bytes securely
     *
     * @param int $length
     * @return string
     */
    private static function generateRandomBytes(int $length): string
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($length);
        } else {
            throw new RuntimeException('No secure random byte generator available');
        }
    }
}
