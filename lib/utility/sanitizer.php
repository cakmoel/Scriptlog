<?php

/**
 * Sanitizer
 *
 * @category Function
 * @author M.Noermoehammad
 * @param string|int $str
 * @param string $type
 *
 */
function sanitizer($str, $type)
{
    $sanitizer = class_exists('Sanitize') ? new Sanitize() : "";
    return $sanitizer->sanitasi(sanitize_string((string) $str), $type);
}

/**
 * sanitize_string
 *
 * @category Function
 * @param string $str
 *
 */
function sanitize_string($str)
{
    $str = class_exists('Sanitize') ? Sanitize::mildSanitizer($str) : "";

    // S8: reuse the shared DbMySQLi instance instead of opening a fresh
    // mysqli connection per call. All queries use PDO prepared statements,
    // so mysqli escaping here is defense-in-depth; the singleton keeps the
    // per-request connection count at one instead of N.
    $mysqli = class_exists('DbMySQLi') ? DbMySQLi::getInstance() : "";

    return $mysqli->filterData($str);
}
