<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * API Authentication
 *
 * Handles token-based authentication for the RESTful API
 * Supports API Key and Bearer Token authentication
 *
 * @category  Core Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

class ApiAuth
{
    /**
     * Authentication types
     */
    public const AUTH_API_KEY = 'api_key';
    public const AUTH_BEARER = 'bearer';
    public const AUTH_NONE = 'none';

    /**
     * Token expiration time (in seconds)
     * Default: 24 hours
     */
    public const TOKEN_EXPIRY = 86400;

    /**
     * Maximum number of failed login attempts before lockout
     */
    public const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds (15 minutes)
     */
    public const LOCKOUT_DURATION = 900;

    /**
     * @var array User data when authenticated
     */
    private static $user = null;

    /**
     * @var string Authentication type used
     */
    private static $authType = self::AUTH_NONE;

    /**
     * @var bool Whether the user is authenticated
     */
    private static $isAuthenticated = false;

    /**
     * Initialize and authenticate the request
     *
     * @return bool Whether authentication was successful
     */
    public static function authenticate()
    {
        // Check for API Key authentication
        $apiKey = self::getApiKey();
        if ($apiKey) {
            return self::authenticateWithApiKey($apiKey);
        }

        // Check for Bearer Token authentication
        $token = self::getBearerToken();
        if ($token) {
            return self::authenticateWithToken($token);
        }

        // No authentication provided - but this might be intentional for public endpoints
        self::$isAuthenticated = false;
        return false;
    }

    /**
     * Authenticate using API Key
     *
     * Looks up the key in the dedicated tbl_api_keys table and verifies
     * it against the stored password_hash(). Falls back to direct comparison
     * for legacy plaintext keys that may exist in tbl_settings.
     *
     * @param string $apiKey The API key
     * @return bool Authentication success
     */
    private static function authenticateWithApiKey($apiKey)
    {
        // Validate API key format
        if (strlen($apiKey) < 32) {
            self::$authType = self::AUTH_API_KEY;
            return false;
        }

        try {
            $dbc = Registry::get('dbc');

            // Look up user by API key hash in the dedicated table
            $sql = "SELECT k.user_id, k.key_hash, k.is_revoked,
                           u.ID, u.user_login, u.user_email, u.user_level,
                           u.user_banned, u.user_locked_until
                    FROM tbl_api_keys k
                    INNER JOIN tbl_users u ON k.user_id = u.ID
                    WHERE k.is_revoked = 0
                    AND (k.expires_at IS NULL OR k.expires_at > NOW())
                    ORDER BY k.id DESC
                    LIMIT 1";

            $stmt = $dbc->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Verify the key against each stored hash
            foreach ($rows as $row) {
                $isValid = false;

                // Try password_verify() first (for hashed keys)
                if (password_verify($apiKey, $row['key_hash'])) {
                    $isValid = true;
                } elseif ($row['key_hash'] === $apiKey) {
                    // Legacy fallback: direct comparison for plaintext keys
                    $isValid = true;
                }

                if (
                    $isValid
                    && !$row['user_banned']
                    && self::isAccountLocked($row) === false
                ) {
                    self::$user = $row;
                    self::$authType = self::AUTH_API_KEY;
                    self::$isAuthenticated = true;

                    // Update last_used_at
                    $updateSql = "UPDATE tbl_api_keys SET last_used_at = NOW() WHERE id = ?";
                    $updateStmt = $dbc->prepare($updateSql);
                    $updateStmt->execute([$row['id']]);

                    // Log successful authentication
                    self::logAccess($row['ID'], true);

                    return true;
                }
            }

            self::logAccess(0, false);
            return false;
        } catch (\Throwable $e) {
            // Log error but don't expose details
            return false;
        }
    }

    /**
     * Authenticate using Bearer Token
     *
     * @param string $token The bearer token
     * @return bool Authentication success
     */
    private static function authenticateWithToken($token)
    {
        // Validate token format
        if (empty($token) || strlen($token) < 32) {
            self::$authType = self::AUTH_BEARER;
            return false;
        }

        try {
            $dbc = Registry::get('dbc');

            // Look up token in user_token table
            $sql = "SELECT u.ID, u.user_login, u.user_email, u.user_level, u.user_banned, u.user_locked_until,
                           t.expired_date, t.is_expired
                    FROM tbl_user_token t
                    INNER JOIN tbl_users u ON t.user_login = u.user_login
                    WHERE t.selector_hash = ? 
                    AND t.is_expired = 0
                    AND t.expired_date > NOW()
                    LIMIT 1";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([substr($token, 0, 16)]); // Use selector part of token
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                // Verify the full token hash
                if (password_verify(substr($token, 16), $result['pwd_hash'])) {
                    if (!$result['user_banned'] && self::isAccountLocked($result) === false) {
                        self::$user = $result;
                        self::$authType = self::AUTH_BEARER;
                        self::$isAuthenticated = true;

                        // Log successful authentication
                        self::logAccess($result['ID'], true);

                        return true;
                    }
                }
            }

            self::logAccess(0, false);
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get API Key from request headers
     *
     * @return string|null
     */
    private static function getApiKey()
    {
        // Check X-API-Key header
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

        if ($apiKey) {
            return $apiKey;
        }

        // Check query string (less secure but useful for testing)
        $apiKey = $_GET['api_key'] ?? null;

        return $apiKey;
    }

    /**
     * Get Bearer Token from request headers
     *
     * @return string|null
     */
    private static function getBearerToken()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public static function isAuthenticated()
    {
        return self::$isAuthenticated;
    }

    /**
     * Get authenticated user data
     *
     * @return array|null
     */
    public static function getUser()
    {
        return self::$user;
    }

    /**
     * Get authenticated user ID
     *
     * @return int|null
     */
    public static function getUserId()
    {
        return self::$user['ID'] ?? null;
    }

    /**
     * Get authenticated user level
     *
     * @return string|null
     */
    public static function getUserLevel()
    {
        return self::$user['user_level'] ?? null;
    }

    /**
     * Get authentication type used
     *
     * @return string
     */
    public static function getAuthType()
    {
        return self::$authType;
    }

    /**
     * Check if user has required permission level
     *
     * @param string|array $requiredLevels Required user level(s)
     * @return bool
     */
    public static function hasPermission($requiredLevels)
    {
        if (!self::$isAuthenticated) {
            return false;
        }

        $userLevel = self::getUserLevel();

        if (is_array($requiredLevels)) {
            return in_array($userLevel, $requiredLevels);
        }

        return $userLevel === $requiredLevels;
    }

    /**
     * Check if account is locked
     *
     * @param array $user User data
     * @return bool
     */
    private static function isAccountLocked($user)
    {
        if (isset($user['user_locked_until']) && !empty($user['user_locked_until'])) {
            $lockedUntil = strtotime($user['user_locked_until']);
            if ($lockedUntil > time()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Log API access attempt
     *
     * @param int $userId User ID (0 if failed)
     * @param bool $success Whether authentication was successful
     */
    private static function logAccess($userId, $success)
    {
        try {
            $dbc = Registry::get('dbc');

            $ipAddress = self::getClientIp();

            if (!$success && $userId === 0) {
                // Check for existing failed attempts
                $sql = "SELECT COUNT(*) as attempts FROM tbl_login_attempt 
                        WHERE ip_address = ? 
                        AND login_date > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";

                $stmt = $dbc->prepare($sql);
                $stmt->execute([$ipAddress]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($result && $result['attempts'] >= self::MAX_LOGIN_ATTEMPTS) {
                    // Too many failed attempts
                    return;
                }
            }

            // Insert login attempt
            $sql = "INSERT INTO tbl_login_attempt (ip_address, login_date) VALUES (?, NOW())";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([$ipAddress]);

            // Clean up old login attempts (older than 24 hours)
            $sql = "DELETE FROM tbl_login_attempt WHERE login_date < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $stmt = $dbc->prepare($sql);
            $stmt->execute();
        } catch (\Throwable $e) {
            // Silently fail - don't break API for logging issues
        }
    }

    /**
     * Validate CSRF token for session-authenticated write requests.
     *
     * CSRF protection is required for POST/PUT/DELETE/PATCH when the request
     * is authenticated via session cookie (no API key or Bearer token).
     * API-key and Bearer-token auth are inherently immune to CSRF.
     *
     * Expects the token in the X-CSRF-Token header.
     *
     * @return void Sends 403 response and exits on failure
     */
    /**
     * Check whether the current request carries API-key or Bearer auth headers.
     *
     * @return bool
     */
    private static function hasApiOrBearerAuth()
    {
        if (!empty($_SERVER['HTTP_X_API_KEY'] ?? '')) {
            return true;
        }
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!empty($auth) && stripos($auth, 'Bearer ') === 0) {
            return true;
        }
        return false;
    }

    public static function validateCsrfForWrite()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return;
        }

        // No active session — nothing to protect, skip CSRF
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        // Skip CSRF check when API-key or Bearer-token auth is present
        // (these are inherently immune to CSRF because the secret is in a header, not a cookie)
        if (self::hasApiOrBearerAuth()) {
            return;
        }

        // Require X-CSRF-Token header
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'status'  => 403,
                'error'   => [
                    'code'    => 'CSRF_MISSING',
                    'message' => 'Missing X-CSRF-Token header. Include the CSRF token for session-authenticated write requests.'
                ]
            ]);
            exit;
        }

        // Verify against session token
        $sessionKey = 'csrf_api_write';
        $storedToken = $_SESSION[$sessionKey] ?? null;
        if ($storedToken === null || !hash_equals($storedToken, $token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'status'  => 403,
                'error'   => [
                    'code'    => 'CSRF_INVALID',
                    'message' => 'Invalid or expired CSRF token. Reload the page and try again.'
                ]
            ]);
            exit;
        }
    }

    /**
     * Generate a CSRF token for API write operations and store it in session.
     *
     * @return string The generated token
     */
    public static function generateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            return '';
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_api_write'] = $token;
        return $token;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private static function getClientIp()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                   'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Set authenticated user from session-based authentication
     *
     * Used by MediaApiController and other admin panel entry points
     * that authenticate via session/cookie rather than API key/token.
     *
     * @param array $userData Must contain 'user_login' and optionally 'user_level'
     * @param string $authType The authentication type (default: 'session')
     * @return void
     */
    public static function setSessionUser(array $userData, $authType = 'session')
    {
        self::$user = $userData;
        self::$authType = $authType;
        self::$isAuthenticated = true;
    }

    /**
     * Get authenticated user login name
     *
     * @return string|null
     */
    public static function getUserLogin()
    {
        return self::$user['user_login'] ?? null;
    }

    /**
     * Generate API key for a user
     *
     * Stores the key hash (bcrypt) in the dedicated tbl_api_keys table
     * and returns the raw key to the caller for one-time display.
     *
     * @param int $userId User ID
     * @param string $description Optional description for the key
     * @return string Generated API key (plaintext, show once)
     */
    public static function generateApiKey($userId, $description = '')
    {
        // Generate a random 32-byte key
        $key = bin2hex(random_bytes(32));

        // Hash the key for storage
        $keyHash = password_hash($key, PASSWORD_BCRYPT);

        try {
            $dbc = Registry::get('dbc');

            // Insert into dedicated api_keys table
            $sql = "INSERT INTO tbl_api_keys (user_id, key_hash, description, created_at)
                    VALUES (?, ?, ?, NOW())";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([(int)$userId, $keyHash, $description]);

            return $key;
        } catch (\Throwable $e) {
            throw new \Exception("Failed to generate API key: " . $e->getMessage());
        }
    }

    /**
     * Revoke all API keys for a user
     *
     * Sets is_revoked = 1 on all active keys for the given user.
     *
     * @param int $userId User ID
     * @return bool Success
     */
    public static function revokeApiKey($userId)
    {
        try {
            $dbc = Registry::get('dbc');

            $sql = "UPDATE tbl_api_keys SET is_revoked = 1, expires_at = NOW() WHERE user_id = ? AND is_revoked = 0";
            $stmt = $dbc->prepare($sql);
            return $stmt->execute([(int)$userId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Revoke a specific API key by ID
     *
     * @param int $keyId The API key ID
     * @return bool Success
     */
    public static function revokeApiKeyById($keyId)
    {
        try {
            $dbc = Registry::get('dbc');

            $sql = "UPDATE tbl_api_keys SET is_revoked = 1, expires_at = NOW() WHERE id = ? AND is_revoked = 0";
            $stmt = $dbc->prepare($sql);
            return $stmt->execute([(int)$keyId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
