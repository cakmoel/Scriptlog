<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class ScriptlogCryptonize
 *
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.1
 * @since Since Release 1.0
 *
 */

use Laminas\Crypt\BlockCipher;
use Laminas\Crypt\Symmetric\Openssl;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\BadFormatException;

class ScriptlogCryptonize
{
    public const METHOD = 'AES-256-CBC';
    public const ENCRYPTION_KEY_LEN = 32;  // 32 bytes for AES-256
    public const HMAC_KEY_LEN = 32;        // 32 bytes for HMAC-SHA256
    public const TOTAL_KEY_LEN = 64;       // 32+32 = 64 bytes total

    /**
     * Generate a secure secret key (64 bytes for encryption+HMAC)
     *
     * @return string
     */
    public static function generateSecretKey(): string
    {
        return self::generateRandomBytes(self::TOTAL_KEY_LEN);
    }

    /**
     * Derive encryption key and HMAC key from master key
     *
     * @param string $masterKey The 64-byte master key
     * @return array Returns ['encryption' => string, 'hmac' => string]
     * @throws ScriptlogCryptonizeException
     */
    private static function deriveKeys(string $masterKey): array
    {
        $keyLength = strlen($masterKey);
        
        if ($keyLength < self::TOTAL_KEY_LEN) {
            // Try to derive from shorter key using HKDF
            if ($keyLength >= self::ENCRYPTION_KEY_LEN) {
                // For backward compatibility: use same key for encryption and HMAC
                // but this is less secure
                error_log("WARNING: Using short key (" . $keyLength . " bytes) - upgrade to 64-byte key");
                return [
                    'encryption' => mb_substr($masterKey, 0, self::ENCRYPTION_KEY_LEN, '8bit'),
                    'hmac' => mb_substr($masterKey, 0, self::HMAC_KEY_LEN, '8bit')
                ];
            }
            
            throw new ScriptlogCryptonizeException(
                "Invalid key length: expected at least " . self::ENCRYPTION_KEY_LEN . 
                " bytes, got " . $keyLength
            );
        }
        
        return [
            'encryption' => mb_substr($masterKey, 0, self::ENCRYPTION_KEY_LEN, '8bit'),
            'hmac' => mb_substr($masterKey, self::ENCRYPTION_KEY_LEN, self::HMAC_KEY_LEN, '8bit')
        ];
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
        $openssl = new Openssl(['algo' => 'aes']);
        $cipher = new BlockCipher($openssl);
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
        $openssl = new Openssl(['algo' => 'aes']);
        $cipher = new BlockCipher($openssl);
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
     * @param Key $key
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
     * @param string $masterKey 64-byte master key (or 32-byte for backward compat)
     * @return string Base64 encoded combined ciphertext
     */
    public static function encryptAES(string $plaintext, string $masterKey): string
    {
        try {
            // Derive separate keys for encryption and HMAC
            $keys = self::deriveKeys($masterKey);
            
            // Generate a random IV
            $iv = self::generateRandomBytes(16);
            
            // Encrypt the plaintext
            $ciphertext = openssl_encrypt(
                $plaintext,
                self::METHOD,
                $keys['encryption'],
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($ciphertext === false) {
                throw new ScriptlogCryptonizeException("Encryption failed: " . openssl_error_string());
            }
            
            // Combine IV and ciphertext for HMAC calculation
            $dataToAuth = $iv . $ciphertext;
            
            // Generate HMAC for authentication
            $hmac = hash_hmac(
                'sha256',
                $dataToAuth,
                $keys['hmac'],
                true
            );
            
            // Combine HMAC + IV + ciphertext and return as base64
            $combined = $hmac . $iv . $ciphertext;
            
            return base64_encode($combined);
            
        } catch (ScriptlogCryptonizeException $e) {
            self::logError($e);
            throw $e;
        }
    }

    /**
     * Decrypt data using AES-256-CBC with HMAC authentication
     *
     * @param string $ciphertextBase64 Base64 encoded combined ciphertext
     * @param string $masterKey 64-byte master key
     * @return string
     * @throws ScriptlogCryptonizeException
     */
    public static function decryptAES(string $ciphertextBase64, string $masterKey): string
    {
        try {
            // Decode from base64
            $ciphertext = base64_decode($ciphertextBase64, true);
            
            if ($ciphertext === false) {
                throw new ScriptlogCryptonizeException("Invalid ciphertext: base64 decoding failed");
            }
            
            // Derive separate keys for encryption and HMAC
            $keys = self::deriveKeys($masterKey);
            
            // Validate ciphertext length (minimum: 32 HMAC + 16 IV + 1 cipher)
            $minLength = 32 + 16 + 1;
            if (strlen($ciphertext) < $minLength) {
                throw new ScriptlogCryptonizeException(
                    "Invalid ciphertext: too short. Expected at least $minLength bytes, got " . strlen($ciphertext)
                );
            }
            
            // Extract HMAC, IV, and ciphertext
            $hmac = mb_substr($ciphertext, 0, 32, '8bit');
            $iv = mb_substr($ciphertext, 32, 16, '8bit');
            $encrypted = mb_substr($ciphertext, 48, null, '8bit');
            
            // Verify HMAC
            $dataToAuth = $iv . $encrypted;
            $expectedHmac = hash_hmac(
                'sha256',
                $dataToAuth,
                $keys['hmac'],
                true
            );
            
            // Use hash_equals for timing-safe comparison
            if (!hash_equals($hmac, $expectedHmac)) {
                // Log additional debug info (but don't expose to user)
                error_log(sprintf(
                    "HMAC verification failed - Lengths: hmac=%d, expected=%d, ciphertext_total=%d",
                    strlen($hmac),
                    strlen($expectedHmac),
                    strlen($ciphertext)
                ));
                throw new ScriptlogCryptonizeException("Invalid ciphertext: HMAC verification failed");
            }
            
            // Decrypt the ciphertext
            $plaintext = openssl_decrypt(
                $encrypted,
                self::METHOD,
                $keys['encryption'],
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($plaintext === false) {
                $error = openssl_error_string();
                throw new ScriptlogCryptonizeException("Decryption failed: " . ($error ?: "unknown error"));
            }
            
            return $plaintext;
            
        } catch (ScriptlogCryptonizeException $e) {
            self::logError($e);
            throw $e;
        }
    }

    /**
     * Load or generate a Defuse\Crypto key
     *
     * @return Key
     */
    public static function scriptlogCipherKey(): Key
    {
        $keyFile = self::getDefuseKeyPath();

        if (file_exists($keyFile)) {
            @chmod($keyFile, 0644);

            if (strpos($keyFile, '.php') !== false) {
                try {
                    $keyAscii = require $keyFile;
                } catch (Throwable $e) {
                    error_log("Failed to load key from {$keyFile}: " . $e->getMessage());
                    $keyObject = Key::createNewRandomKey();
                    $keyAscii = $keyObject->saveToAsciiSafeString();
                    self::saveKeyToFile($keyAscii, dirname($keyFile));
                    return $keyObject;
                }
            } else {
                $keyAscii = file_get_contents($keyFile);
                if ($keyAscii === false) {
                    throw new RuntimeException("Cannot read key file: {$keyFile}");
                }
            }
            
            // Validate the key
            try {
                return Key::loadFromAsciiSafeString(trim($keyAscii));
            } catch (BadFormatException $e) {
                error_log("Invalid key format in {$keyFile}: " . $e->getMessage());
                // Generate new key
                $keyObject = Key::createNewRandomKey();
                self::saveKeyToFile($keyObject->saveToAsciiSafeString(), dirname($keyFile));
                return $keyObject;
            }
        } else {
            $keyObject = Key::createNewRandomKey();
            $keyAscii = $keyObject->saveToAsciiSafeString();

            $keyDir = dirname($keyFile);
            if (!is_dir($keyDir)) {
                @mkdir($keyDir, 0700, true);
            }

            if (!is_dir($keyDir) || !is_writable($keyDir)) {
                $keyDir = dirname(__DIR__, 2) . '/lib/utility/.lts';
                if (!is_dir($keyDir)) {
                    @mkdir($keyDir, 0700, true);
                }
            }

            @chmod($keyDir, 0700);
            $newKeyFile = self::saveKeyToFile($keyAscii, $keyDir);
            self::updateConfigKeyPath($newKeyFile);
            
            return $keyObject;
        }
    }
    
    /**
     * Update config.php and .env with new key path
     *
     * @param string $newKeyPath Absolute path to the key file
     */
    private static function updateConfigKeyPath(string $newKeyPath): void
    {
        $rootDir = dirname(__DIR__, 2);
        $configPath = $rootDir . '/config.php';

        if (file_exists($configPath) && is_writable($configPath)) {
            $config = require $configPath;
            $config['app']['defuse_key'] = $newKeyPath;
            $content = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';' . PHP_EOL;
            file_put_contents($configPath, $content);
        }

        $envPath = $rootDir . '/.env';
        if (file_exists($envPath) && is_writable($envPath)) {
            $envContent = file_get_contents($envPath);
            $escapedPath = addslashes($newKeyPath);
            $envContent = preg_replace(
                '/^DEFUSE_KEY_PATH=.*$/m',
                'DEFUSE_KEY_PATH=' . $escapedPath,
                $envContent
            );
            file_put_contents($envPath, $envContent);
        }
    }

    /**
     * Resolve a path - returns absolute path as-is, resolves relative from app root
     *
     * @param string $path
     * @return string
     */
    private static function resolvePath(string $path): string
    {
        if ($path[0] === '/' || (strlen($path) > 1 && $path[1] === ':')) {
            return $path;
        }

        $resolved = realpath(dirname(__DIR__, 2) . '/' . $path);
        return $resolved ?: (dirname(__DIR__, 2) . '/' . $path);
    }

    /**
     * Get the Defuse key path - checks config, database, then scans default directory for existing keys
     *
     * @return string
     */
    private static function getDefuseKeyPath(): string
    {
        $configPath = dirname(__DIR__, 2) . '/config.php';
        $appRoot = dirname(__DIR__, 2);
        $parentDir = dirname($appRoot);
        $defaultKeyDir = $appRoot . '/lib/utility/.lts';
        $storageKeyDir = $parentDir . '/storage/keys';

        $dbKeyPath = self::getKeyPathFromDatabase($configPath);
        if ($dbKeyPath !== null && file_exists($dbKeyPath)) {
            return $dbKeyPath;
        }

        if (file_exists($configPath)) {
            $config = require $configPath;
            if (isset($config['app']['defuse_key'])) {
                $resolved = self::resolvePath($config['app']['defuse_key']);
                
                if (file_exists($resolved)) {
                    return $resolved;
                }
            }
        }

        if (is_dir($storageKeyDir)) {
            $keyFiles = glob($storageKeyDir . '/*.php');
            if (!empty($keyFiles)) {
                return $keyFiles[0];
            }
        }

        if (is_dir($defaultKeyDir)) {
            $keyFiles = glob($defaultKeyDir . '/*.php');
            if (!empty($keyFiles)) {
                return $keyFiles[0];
            }
        }

        return self::generateSecureKey($storageKeyDir);
    }

    /**
     * Get defuse key path from database settings
     *
     * @param string $configPath
     * @return string|null
     */
    private static function getKeyPathFromDatabase(string $configPath): ?string
    {
        if (!file_exists($configPath)) {
            return null;
        }

        $config = require $configPath;
        
        $dbHost = $config['db']['host'] ?? null;
        $dbUser = $config['db']['user'] ?? null;
        $dbPass = $config['db']['pass'] ?? null;
        $dbName = $config['db']['name'] ?? null;
        $dbPort = $config['db']['port'] ?? '3306';
        $prefix = $config['db']['prefix'] ?? '';

        if (!$dbHost || !$dbUser || !$dbName) {
            return null;
        }

        try {
            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
            
            if ($mysqli->connect_error) {
                return null;
            }

            $stmt = $mysqli->prepare("SELECT setting_value FROM {$prefix}tbl_settings WHERE setting_name = ?");
            $settingName = 'defuse_key_path';
            $stmt->bind_param('s', $settingName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $mysqli->close();
                return $row['setting_value'];
            }
            
            $mysqli->close();
        } catch (Exception $e) {
            error_log("Error reading defuse_key_path from database: " . $e->getMessage());
        }

        return null;
    }
    
    /**
     * Generate a new secure key in the default directory
     *
     * @param string $keyDir
     * @return string
     */
    private static function generateSecureKey(string $keyDir): string
    {
        if (!is_dir($keyDir)) {
            @mkdir($keyDir, 0755, true);
        }
        
        if (strpos($keyDir, '/lib/') !== false && !file_exists($keyDir . '/.htaccess')) {
            $htaccessContent = "# Deny all public access to encryption keys\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";
            @file_put_contents($keyDir . '/.htaccess', $htaccessContent);
        }
        
        $keyObject = Key::createNewRandomKey();
        $keyAscii = $keyObject->saveToAsciiSafeString();
        
        $newKeyFile = self::saveKeyToFile($keyAscii, $keyDir);
        self::updateConfigKeyPath($newKeyFile);
        
        return $newKeyFile;
    }

    /**
     * Load encryption key from PHP file
     *
     * @return string
     */
    private static function loadEncryptionKey(): string
    {
        $keyPath = self::getDefuseKeyPath();
        
        if (file_exists($keyPath)) {
            if (strpos($keyPath, '.php') !== false) {
                $key = require $keyPath;
                return $key;
            } else {
                $key = file_get_contents($keyPath);
                if ($key === false) {
                    throw new RuntimeException('Cannot read encryption key from: ' . $keyPath);
                }
                return $key;
            }
        }

        throw new RuntimeException('Encryption key not found at: ' . $keyPath);
    }

    /**
     * Generate random bytes securely
     *
     * @param int $length
     * @return string
     * @throws RuntimeException
     */
    private static function generateRandomBytes(int $length): string
    {
        try {
            return random_bytes($length);
        } catch (Exception $e) {
            if (function_exists('openssl_random_pseudo_bytes')) {
                $strong = false;
                $bytes = openssl_random_pseudo_bytes($length, $strong);
                if ($strong && $bytes !== false) {
                    return $bytes;
                }
            }
            throw new RuntimeException('No secure random byte generator available: ' . $e->getMessage());
        }
    }

    /**
     * Save encryption key to a file in the given directory
     *
     * @param string $keyAscii ASCII-safe key string
     * @param string $keyDir Directory to save the key
     * @return string Path to the saved key file
     */
    private static function saveKeyToFile(string $keyAscii, string $keyDir): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $filename = '';
        for ($i = 0; $i < 16; $i++) {
            $filename .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $newKeyFile = $keyDir . '/' . $filename . '.php';

        $phpContent = "<?php\n// Encryption key generated on " . date('Y-m-d H:i:s') . "\n// Do not delete or modify this file\nreturn '" . addslashes($keyAscii) . "';";
        file_put_contents($newKeyFile, $phpContent);
        @chmod($newKeyFile, 0644);

        return $newKeyFile;
    }

    /**
     * Log encryption errors
     *
     * @param Exception $e
     */
    private static function logError(Exception $e): void
    {
        if (class_exists('LogError')) {
            LogError::setStatusCode(http_response_code());
            LogError::newMessage($e);
            LogError::customErrorMessage('admin');
        } else {
            error_log('ScriptlogCryptonize Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}

