<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * app_settings_cache()
 *
 * Request-scoped holder for the memoized tbl_settings map.
 * Accessed by reference so tests can reset it between cases.
 *
 * @category function
 * @author   Scriptlog Engineering
 * @license  MIT
 * @return array|null
 *
 */
function &app_settings_cache()
{
    static $settings = null;
    return $settings;
}

/**
 * app_settings()
 *
 * Load all tbl_settings rows ONCE per request into a request-scoped static
 * map keyed by setting_name.
 *
 * Settings only change through the admin panel in a separate request, so a
 * single read per request is safe and correct. Supports both the Db (PDO
 * wrapper) and Medoo connection objects exposed by medoo_init().
 *
 * @category function
 * @author   Scriptlog Engineering
 * @license  MIT
 * @return array<string,string>
 *
 */
function app_settings()
{
    $settings = &app_settings_cache();

    if ($settings !== null) {
        return $settings;
    }

    $settings = array();

    $database = function_exists('medoo_init') ? medoo_init() : "";

    $rows = null;

    if (is_object($database) && method_exists($database, 'select')) {
        $rows = $database->select("tbl_settings", ["setting_name", "setting_value"]);
    } elseif (is_object($database) && method_exists($database, 'dbSelect')) {
        $rows = $database->dbSelect(
            "SELECT setting_name, setting_value FROM tbl_settings",
            array(),
            PDO::FETCH_ASSOC
        );
    }

    if (is_array($rows)) {
        foreach ($rows as $data) {
            if (is_array($data)) {
                $name = isset($data['setting_name']) ? $data['setting_name'] : '';
                $value = isset($data['setting_value']) ? $data['setting_value'] : '';
            } else {
                $name = isset($data->setting_name) ? $data->setting_name : '';
                $value = isset($data->setting_value) ? $data->setting_value : '';
            }

            if ($name !== '') {
                $settings[(string)$name] = (string)$value;
            }
        }
    }

    return $settings;
}

/**
 * app_setting()
 *
 * Read a single setting value by name from the request-scoped settings map.
 * Falls back to $default when the setting is missing or blank.
 *
 * @category function
 * @author   Scriptlog Engineering
 * @license  MIT
 * @param string $name
 * @param string $default
 * @return string
 *
 */
function app_setting($name, $default = '')
{
    $settings = function_exists('app_settings') ? app_settings() : array();

    return (isset($settings[$name]) && $settings[$name] !== '') ? $settings[$name] : $default;
}

/**
 * reset_app_settings_cache()
 *
 * Forget the memoized settings map (used by tests and after admin writes).
 *
 * @category function
 * @author   Scriptlog Engineering
 * @license  MIT
 * @return void
 *
 */
function reset_app_settings_cache()
{
    $settings = &app_settings_cache();
    $settings = null;
}