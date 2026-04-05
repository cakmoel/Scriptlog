<?php

/**
 * app_key()
 *
 * Retrieves the application key from the configuration file.
 * 
 * The application key is generated during installation using cryptographically
 * secure random number generation and stored in both config.php and .env file.
 *
 * Note: This function reads ONLY from the configuration file (config.php).
 * It does NOT verify the key against the database. Use check_app_key() 
 * if you need to compare with the database value.
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see check_app_key() For comparing key with database value
 * @see is_valid_app_key() For validating key strength
 * @see is_placeholder_key() For detecting placeholder keys
 * @return string The application key, or empty string if not configured
 *
 */
function app_key()
{
    $config = class_exists('AppConfig') ? AppConfig::readConfiguration(invoke_config()) : [];

    $configKey = isset($config['app']['key']) ? $config['app']['key'] : "";

    return (!empty($configKey)) ? $configKey : "";
}

/**
 * check_app_key()
 *
 * Verifies if a provided key matches the database value.
 * This function compares the given key against the app_key stored in 
 * the tbl_settings database table.
 *
 * @category function
 * @param string $key The key to verify
 * @return bool True if keys match, false otherwise
 * @see app_key() For reading key from config file only
 *
 */
function check_app_key($key)
{
    $appKey = false;
    $grabKey = grab_data_key();

    if ($key === $grabKey) {
        $appKey = true;
    } elseif (strcmp($key, $grabKey) === 0) {
        $appKey = true;
    } else {
        $appKey = false;
    }

    return $appKey;
}

/**
 * grab_data_key()
 *
 * Retrieves the application key from the database (tbl_settings).
 *
 * @category function
 * @return mixed The key value from database, or null if not found
 *
 */
function grab_data_key()
{
    if (function_exists('medoo_get_where')) {
        return medoo_get_where("tbl_settings", "setting_value", [
          "setting_name" => "app_key"
        ]);
    }
}

/**
 * is_valid_app_key()
 *
 * Validates if the given key meets minimum security requirements.
 * 
 * Requirements:
 * - Minimum length of 20 characters
 * - Must contain at least one uppercase letter
 * - Must contain at least one number
 *
 * @category function
 * @param string $key The application key to validate
 * @return bool True if key meets requirements, false otherwise
 *
 */
function is_valid_app_key($key)
{
    if (empty($key)) {
        return false;
    }

    if (strlen($key) < 20) {
        return false;
    }

    if (!preg_match('/[A-Z]/', $key)) {
        return false;
    }

    if (!preg_match('/[0-9]/', $key)) {
        return false;
    }

    return true;
}

/**
 * is_placeholder_key()
 *
 * Detects if a key is a placeholder/default value.
 * Placeholder keys should never be used in production.
 *
 * @category function
 * @param string $key The application key to check
 * @return bool True if key appears to be a placeholder, false otherwise
 *
 */
function is_placeholder_key($key)
{
    if (empty($key)) {
        return true;
    }

    $placeholderPatterns = [
        'XXXXXX-XXXXXX-XXXXXX-XXXXXX',
        'PLACEHOLDER',
        'CHANGE-ME',
        'YOUR-KEY-HERE',
        'xxxxxx-xxxxxx-xxxxxx-xxxxxx',
    ];

    foreach ($placeholderPatterns as $pattern) {
        if (strpos($key, $pattern) !== false) {
            return true;
        }
    }

    if (preg_match('/^X{6,}/', $key)) {
        return true;
    }

    return false;
}

/**
 * validate_app_key()
 *
 * Comprehensive validation of the application key.
 * Returns array with validation result and any warnings.
 *
 * @category function
 * @param string $key The application key to validate
 * @return array ['valid' => bool, 'warnings' => array]
 *
 */
function validate_app_key($key)
{
    $warnings = [];
    $isValid = true;

    if (empty($key)) {
        $warnings[] = 'Application key is empty';
        $isValid = false;
    }

    if (is_placeholder_key($key)) {
        $warnings[] = 'Application key appears to be a placeholder value';
        $isValid = false;
    }

    if (!is_valid_app_key($key)) {
        $warnings[] = 'Application key does not meet minimum security requirements';
        $isValid = false;
    }

    return [
        'valid' => $isValid,
        'warnings' => $warnings
    ];
}
