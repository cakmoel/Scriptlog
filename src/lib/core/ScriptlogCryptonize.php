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
    public const METHOD = 'AES-256-CBC';

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
        $keyFile = self::getDefuseKeyPath();

        if (file_exists($keyFile)) {
            // Ensure key file is readable by web server
            @chmod($keyFile, 0644);

            // Handle both old .txt format and new .php format
            if (strpos($keyFile, '.php') !== false) {
                // New PHP format: return 'def00000...'
                try {
                    $keyAscii = require $keyFile;
                } catch (Throwable $e) {
                    // Key file cannot be read (permissions or corruption) - generate new key
                    $keyObject = Key::createNewRandomKey();
                    $keyAscii = $keyObject->saveToAsciiSafeString();
                    self::saveKeyToFile($keyAscii, dirname($keyFile));
                    return $keyObject;
                }
            } else {
                // Old .txt format (for backward compatibility)
                $keyAscii = file_get_contents($keyFile);
            }
        } else {
            // Key doesn't exist, generate new one with random filename
            $keyObject = Key::createNewRandomKey();
            $keyAscii = $keyObject->saveToAsciiSafeString();

            // Save as PHP format with random filename
            $keyDir = dirname($keyFile);
            if (!is_dir($keyDir)) {
                @mkdir($keyDir, 0700, true);
            }

            // Fallback: if target directory is not writable (e.g. /etc/ssl/keys),
            // fall back to default location inside app
            if (!is_dir($keyDir) || !is_writable($keyDir)) {
                $keyDir = dirname(__DIR__, 2) . '/lib/utility/.lts';
                if (!is_dir($keyDir)) {
                    @mkdir($keyDir, 0700, true);
                }
            }

            // Security: lock down key directory permissions
            @chmod($keyDir, 0700);

            // Generate random filename
            $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $filename = '';
            for ($i = 0; $i < 16; $i++) {
                $filename .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $newKeyFile = $keyDir . '/' . $filename . '.php';

            $phpContent = "<?php\n// Encryption key generated on " . date('Y-m-d H:i:s') . "\n// Do not delete or modify this file\nreturn '$keyAscii';";
            file_put_contents($newKeyFile, $phpContent);

            // Security: key file readable by web server
            @chmod($newKeyFile, 0644);

            // Update config and .env with new key path (absolute)
            self::updateConfigKeyPath($newKeyFile);
        }

        return Key::loadFromAsciiSafeString($keyAscii);
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
            file_put_contents($configPath, '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';' . PHP_EOL);
        }

        // Also update .env if it exists and is writable
        $envPath = $rootDir . '/.env';
        if (file_exists($envPath) && is_writable($envPath)) {
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace(
                '/^DEFUSE_KEY_PATH=.*$/m',
                'DEFUSE_KEY_PATH=' . $newKeyPath,
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
        // Absolute path (Unix / or Windows C:\) - use as-is
        if ($path[0] === '/' || (strlen($path) > 1 && $path[1] === ':')) {
            return $path;
        }

        // Relative path - resolve from app root
        return realpath(dirname(__DIR__, 2) . '/' . $path) ?: (dirname(__DIR__, 2) . '/' . $path);
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

        // First, try to get key path from database settings
        $dbKeyPath = self::getKeyPathFromDatabase($configPath);
        if ($dbKeyPath !== null && file_exists($dbKeyPath)) {
            return $dbKeyPath;
        }

        if (file_exists($configPath)) {
            $config = require $configPath;
            if (isset($config['app']['defuse_key'])) {
                $resolved = self::resolvePath($config['app']['defuse_key']);
                
                // SECURITY: Validate the configured key path is secure
                if (file_exists($resolved)) {
                    $keyPath = $config['app']['defuse_key'];
                    $relativePath = ltrim($keyPath, '/\\');
                    
                    // Check if key is in a publicly accessible directory - dynamically determine
                    $isSecure = false;
                    $allowedPrefixes = [
                        $appRoot . '/lib/',
                        $storageKeyDir,
                        $parentDir . '/storage/',
                    ];
                    
                    foreach ($allowedPrefixes as $prefix) {
                        if (strpos($resolved, $prefix) === 0) {
                            $isSecure = true;
                            break;
                        }
                    }
                    
                    // If key path is not secure, fall back to default
                    if (!$isSecure) {
                        error_log("SECURITY WARNING: Configured defuse_key path '$resolved' is not secure. Falling back to default location.");
                        
                        // Look for key in default directory
                        if (is_dir($defaultKeyDir)) {
                            $existingKeys = glob($defaultKeyDir . '/*.php');
                            if (!empty($existingKeys)) {
                                return $existingKeys[0];
                            }
                        }
                        
                        // If no existing key in default, generate new one
                        return self::generateSecureKey($defaultKeyDir);
                    }
                    
                    return $resolved;
                }

                // Config path doesn't exist — check if there's an existing key in default directory
                if (is_dir($defaultKeyDir)) {
                    $existingKeys = glob($defaultKeyDir . '/*.php');
                    if (!empty($existingKeys)) {
                        return $existingKeys[0];
                    }
                }

                // Also check storage directory outside web root
                if (is_dir($storageKeyDir)) {
                    $existingKeys = glob($storageKeyDir . '/*.php');
                    if (!empty($existingKeys)) {
                        return $existingKeys[0];
                    }
                }
            }
        }

        // Scan storage directory first (preferred location)
        if (is_dir($storageKeyDir)) {
            $keyFiles = glob($storageKeyDir . '/*.php');
            if (!empty($keyFiles)) {
                return $keyFiles[0];
            }
        }

        // Scan default directory for existing key files
        if (is_dir($defaultKeyDir)) {
            $keyFiles = glob($defaultKeyDir . '/*.php');
            if (!empty($keyFiles)) {
                return $keyFiles[0];
            }
        }

        // No key exists — generate new one in secure location (prefer storage)
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
        
        // Check if database credentials are available
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
        
        // Create .htaccess protection
        if (strpos($keyDir, '/lib/') !== false && !file_exists($keyDir . '/.htaccess')) {
            $htaccessContent = "# Deny all public access to encryption keys\nOrder deny,allow\nDeny from all\n";
            @file_put_contents($keyDir . '/.htaccess', $htaccessContent);
        }
        
        // Generate random filename
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $filename = '';
        for ($i = 0; $i < 16; $i++) {
            $filename .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $newKeyFile = $keyDir . '/' . $filename . '.php';
        
        $keyObject = Key::createNewRandomKey();
        $keyAscii = $keyObject->saveToAsciiSafeString();
        
        $phpContent = "<?php\n// Encryption key generated on " . date('Y-m-d H:i:s') . "\n// Do not delete or modify this file\nreturn '$keyAscii';";
        file_put_contents($newKeyFile, $phpContent);
        chmod($newKeyFile, 0644);
        
        // Update config to point to new key
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
            // Handle both old .txt format and new .php format
            if (strpos($keyPath, '.php') !== false) {
                // New PHP format: return 'def00000...'
                $key = require $keyPath;
                return $key;
            } else {
                // Old .txt format (for backward compatibility)
                return file_get_contents($keyPath);
            }
        }

        throw new RuntimeException('Encryption key not found at: ' . $keyPath);
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

        $phpContent = "<?php\n// Encryption key generated on " . date('Y-m-d H:i:s') . "\n// Do not delete or modify this file\nreturn '$keyAscii';";
        file_put_contents($newKeyFile, $phpContent);

        @chmod($newKeyFile, 0644);

        return $newKeyFile;
    }
}
