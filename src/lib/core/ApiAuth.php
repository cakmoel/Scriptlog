<?php

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

        // Look up API key in database
        try {
            $dbc = Registry::get('dbc');

            $sql = "SELECT u.ID, u.user_login, u.user_email, u.user_level, u.user_banned, u.user_locked_until
                    FROM tbl_users u
                    INNER JOIN tbl_settings s ON s.setting_name = 'api_key' AND s.setting_value = ?
                    WHERE u.ID = s.ID";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$apiKey]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && !$user['user_banned'] && self::isAccountLocked($user) === false) {
                self::$user = $user;
                self::$authType = self::AUTH_API_KEY;
                self::$isAuthenticated = true;

                // Log successful authentication
                self::logAccess($user['ID'], true);

                return true;
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
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

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
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $dbc->query($sql);
        } catch (\Throwable $e) {
            // Silently fail - don't break API for logging issues
        }
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
     * Generate API key for a user
     *
     * @param int $userId User ID
     * @return string Generated API key
     */
    public static function generateApiKey($userId)
    {
        // Generate a random 32-byte key
        $key = bin2hex(random_bytes(32));

        // Store in settings table (in production, use a separate table)
        try {
            $dbc = Registry::get('dbc');

            // Check if user already has an API key
            $sql = "SELECT ID FROM tbl_settings WHERE setting_name = 'api_key_user_" . (int)$userId . "'";
            $stmt = $dbc->query($sql);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing
                $sql = "UPDATE tbl_settings SET setting_value = ? WHERE setting_name = 'api_key_user_" . (int)$userId . "'";
                $stmt = $dbc->prepare($sql);
                $stmt->execute([$key]);
            } else {
                // Insert new
                $sql = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES ('api_key_user_" . (int)$userId . "', ?)";
                $stmt = $dbc->prepare($sql);
                $stmt->execute([$key]);
            }

            return $key;
        } catch (\Throwable $e) {
            throw new \Exception("Failed to generate API key: " . $e->getMessage());
        }
    }

    /**
     * Revoke API key for a user
     *
     * @param int $userId User ID
     * @return bool Success
     */
    public static function revokeApiKey($userId)
    {
        try {
            $dbc = Registry::get('dbc');

            $sql = "DELETE FROM tbl_settings WHERE setting_name = 'api_key_user_" . (int)$userId . "'";
            $stmt = $dbc->prepare($sql);
            return $stmt->execute();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
