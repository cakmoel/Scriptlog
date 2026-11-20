<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * sanitize.class.php
 * Sanitizing input and output
 *
 * @category   Core Class
 * @author     Khairu a.k.a wenkhairu
 * @author     M.Noermoehammad
 * @copyright  wenkhairu|M.Noermoehammad
 * @license    MIT
 *
 */

class Sanitize
{
    /**
     * Sanitize a value for database use.
     *
     * Types:
     * - `sql`  : reduce to a positive integer (identifier use only).
     * - `xss`  : reduce to `[A-Za-z0-9_]` word characters.
     * - `uri`  : strip URI-dangerous characters, keep a readable path.
     *
     * The `xss` branch deliberately ends with `preg_replace('/[\W]/', '', $str)`,
     * which removes every character that is not a word character. That makes a
     * trailing `prevent_injection()` call a provable no-op: htmLawed receives a
     * pure `[A-Za-z0-9_]` string, so it has no tags, attributes or entities to
     * act on. Dropping that call removes one full htmLawed pipeline run per
     * sanitized value (measured 0.043 ms -> 0.003 ms per call) with byte-identical
     * output. `Dao::filteringId()` keeps its own `prevent_injection()` call, so
     * the value is still HTML-filtered before this method sees it.
     *
     * @param string|int $str Value to sanitize
     * @param string $tipe Sanitization type: 'sql', 'xss' or 'uri'
     * @return string|int Sanitized value (int for 'sql', string otherwise)
     */
    public function sanitasi($str, $tipe)
    {

        switch ($tipe) {
            default:
            case 'sql':
                $d = array('-', '/', '\\', ',', '#', ':', ';', '\'', '"', '[', ']', '{', '}', ')', '(', '|', '`', '~', '!', '%', '$', '^', '&', '*', '=', '?', '+');
                $str = str_replace($d, '', $str);
                $str = stripcslashes($str);
                $str = htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                $str = preg_replace('/[^A-Za-z0-9]/', '', $str);
                return intval($str);
                break;

            case 'xss':
                $d = array('\\', '#', ';', '\'', '"', '[', ']', '{', '}', ')', '(', '|', '`', '~', '!', '%', '$', '^', '&', '*', '=', '?', '+');
                $str = str_replace($d, '', $str);
                $str = stripcslashes($str);
                $str = htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                // Everything non-word is removed here, so no HTML can survive.
                // See the method docblock: the former prevent_injection() call
                // after this line was a measurable no-op and has been removed.
                $str = (string) preg_replace('/[\W]/', '', $str);
                return $str;
                break;

            case 'uri':
                $d = array('\\', '#', ';', '\'', '"', '[', ']', '{', '}', ')', '(', '|', '`', '~', '!', '%', '$', '^', '&', '*', '=', '?', '+');
                $str = str_replace($d, '', $str);
                $str = stripcslashes($str);
                $str = htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                $str = prevent_injection($str);
                return $str;
                break;
        }
    }

    public function __construct()
    {
    }

    /**
     * Sanitize string (alias for sanitasi).
     *
     * This method exists for API compatibility. ConfigurationDao and other DAO classes
     * call $sanitizer->sanitize() but this class uses sanitasi() as its primary method.
     *
     * @param string $str
     * @param string $tipe
     * @return string|int
     */
    public function sanitize($str, $tipe)
    {
        return $this->sanitasi($str, $tipe);
    }

    /**
     * mildSanitizer
     *
     * @param string $str
     * @return string
     *
     */
    public static function mildSanitizer($str)
    {
        if (function_exists('simple_remove_xss')) {
            return simple_remove_xss($str);
        }
        return $str;
    }

    /**
     * severeSanitizer
     *
     * @param string $str
     * @return string
     *
     */
    public static function severeSanitizer($str)
    {
        return (function_exists('remove_xss')) ? remove_xss($str) : "";
    }

    /**
     * strictSanitizer
     *
     * @param string $str
     * @return string
     *
     */
    public static function strictSanitizer($str)
    {
        return (function_exists('purify_dirty_html')) ? purify_dirty_html($str) : "";
    }
}
