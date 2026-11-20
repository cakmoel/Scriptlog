<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Request-scoped Escaper instance cache keyed by encoding.
 *
 * Laminas\Escaper\Escaper is immutable once constructed (it only stores the
 * encoding and the htmlspecialchars flags), but escape_html() used to build a
 * new one on every call - 43 constructions per homepage render in the
 * September 2026 profile. Reusing one instance per encoding keeps the escaping
 * behaviour identical while removing that allocation churn.
 *
 * @category function
 * @return array<string,\Laminas\Escaper\Escaper>
 *
 */
function &escape_html_escaper_cache()
{
    static $escapers = array();
    return $escapers;
}

/**
 * escape_html()
 *
 * Escape HTML, HTML Atrributes, JavaScript, CSS and Url.
 * This function for escaping data for output not be misused for filtering input data
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $input The input string
 * @param string $type
 * @param string $encoding Character encoding
 * @param string $mode | mode = base or mode = various
 * @uses laminas-escaper
 * @see https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
 * @see https://docs.laminas.dev/laminas-escaper/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-html/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-html-attributes/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-javascript/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-css/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-url/
 * @return string|null
 *
 */
function escape_html($input, $type = 'html', $encoding = 'utf-8', $mode = 'base')
{

    if ($input === null) {
        return '';
    }

    if (is_string($encoding)) {
        $escapers = &escape_html_escaper_cache();

        if (!isset($escapers[$encoding])) {
            $escapers[$encoding] = new Laminas\Escaper\Escaper($encoding);
        }

        $escaper = $escapers[$encoding];
    } else {
        // Preserve the historical failure mode for a non-string encoding.
        // The parameter is untyped at runtime, so this branch stays reachable
        // even though the documented contract is a string.
        /** @psalm-suppress NoValue */
        $escaper = new Laminas\Escaper\Escaper($encoding);
    }

    switch ($mode) {
        default:
        case 'base':
            return base_escape($input, $type, $escaper);

        case 'various':
            return various_escape($input, $type, $escaper);
    }

    return '';
}

/**
 * base_escape
 *
 * @param string $input
 * @param string $type
 * @param object $escaper
 * @return string|null
 */
function base_escape($input, $type, $escaper)
{

    if ($type === 'html_attributes') {
        return is_object($escaper) ? $escaper->escapeHtmlAttr($input) : "";
    } elseif ($type === 'html') {
        return is_object($escaper) ? $escaper->escapeHtml($input) : "";
    }

    return null;
}

/**
 * various_escape
 *
 * @param string $input
 * @param string $type
 * @param object $escaper
 */
function various_escape($input, $type, $escaper)
{

    if ($type === 'js') {
        return is_object($escaper) ? $escaper->escapeJs($input) : "";
    } elseif ($type === 'css') {
        return is_object($escaper) ? $escaper->escapeCss($input) : "";
    } elseif ($type === 'url') {
        return is_object($escaper) ? $escaper->escapeUrl($input) : "";
    }
}
