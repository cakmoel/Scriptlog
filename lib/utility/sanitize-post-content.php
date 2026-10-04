<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * sanitize_post_content()
 *
 * Sanitizes post content for safe output in the admin editor and the public
 * single-post renderer. Stored post content is entity-encoded at save time
 * (FILTER_SANITIZE_FULL_SPECIAL_CHARS), so the double html_entity_decode is
 * required before the htmLawed whitelist can inspect the real markup. The
 * pipeline mirrors the one previously kept inline in ProtectedPostService:
 * decode twice, strip inline style attributes, strip every on* event-handler
 * attribute (a fixed deny list cannot enumerate all handlers — RCE audit
 * follow-up), neutralize dangerous URL schemes, then run htmLawed with the
 * event-handler/style blacklist as a second layer.
 *
 * @category function
 * @param string $content Raw, entity-encoded post content
 * @param string|null $cacheDir Optional file-cache directory (tests only)
 * @return string Sanitized HTML safe to embed in the editor or template
 */
function sanitize_post_content($content, $cacheDir = null)
{
    // S4: remember htmLawed output per post version. The cache is
    // content-addressed (sha1 of filter version + content), so updating a
    // post naturally yields a new key and can never serve stale cleaning;
    // a filter-rule change bumps the version and invalidates everything.
    // In-memory map first (repeat calls in one request), file cache next
    // (repeat views across requests).
    static $memory = array();

    $version = 'v2:' . implode(',', post_content_deny_attributes());
    $key = sha1($version . "\0" . (string)$content);

    if (isset($memory[$key])) {
        return $memory[$key];
    }

    if ($cacheDir === null) {
        $cacheDir = defined('APP_ROOT')
            ? APP_ROOT . 'public' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sanitize' . DIRECTORY_SEPARATOR
            : "";
    }
    $cacheFile = $cacheDir !== "" ? $cacheDir . $key . '.html' : "";

    if ($cacheFile !== "" && is_file($cacheFile)) {
        $cached = @file_get_contents($cacheFile);
        if (is_string($cached)) {
            $memory[$key] = $cached;
            return $cached;
        }
    }

    $clean = sanitize_post_content_uncached($content);

    if ($cacheFile !== "" && (!is_dir($cacheDir) || is_writable($cacheDir))) {
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $htaccess = $cacheDir . '.htaccess';
        if (is_dir($cacheDir) && !file_exists($htaccess)) {
            @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
        }
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            @file_put_contents($cacheFile, $clean, LOCK_EX);
        }
    }

    $memory[$key] = $clean;
    if (count($memory) > 20) {
        array_shift($memory);
    }

    return $clean;
}

/**
 * sanitize_post_content_uncached()
 *
 * The raw decode + strip + htmLawed pipeline, without any caching.
 *
 * @param string $content Raw, entity-encoded post content
 * @return string Sanitized HTML safe to embed in the editor or template
 */
function sanitize_post_content_uncached($content)
{
    $decoded = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    $decoded = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');

    $clean = preg_replace('/\s*style="[^"]*"/', '', $decoded) ?? '';
    $clean = preg_replace('/\s*style=[^>\s]*/', '', $clean) ?? '';

    $clean = strip_event_handler_attributes($clean);
    $clean = neutralize_dangerous_url_schemes($clean);

    if (function_exists('htmLawed')) {
        return htmLawed($clean, array(
            'deny_attribute' => implode(',', post_content_deny_attributes()),
            'keep_bad' => 0
        ));
    }

    return $clean;
}

/**
 * strip_event_handler_attributes()
 *
 * Removes every inline event-handler attribute (onclick, onerror,
 * onpointerenter, ontoggle, onanimationstart, ...) from an HTML string.
 * A fixed deny list cannot enumerate all current and future handler names,
 * so the generic on* pattern is the enforcement layer; the
 * post_content_deny_attributes() list remains as documentation and as the
 * htmLawed second layer.
 *
 * @category function
 * @param string $html Decoded HTML markup
 * @return string HTML with event-handler attributes removed
 */
function strip_event_handler_attributes($html)
{
    $stripped = preg_replace(
        '/\s+on[a-zA-Z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>"\']+)/i',
        '',
        (string)$html
    );

    return ($stripped === null) ? (string)$html : $stripped;
}

/**
 * neutralize_dangerous_url_schemes()
 *
 * Rewrites dangerous URL schemes (javascript:, vbscript:, data:text/html)
 * in link/scriptable attributes so surviving markup cannot execute script.
 *
 * @category function
 * @param string $html Decoded HTML markup
 * @return string HTML with dangerous schemes neutralized
 */
function neutralize_dangerous_url_schemes($html)
{
    $neutralized = preg_replace(
        '/(\s+(?:href|src|xlink:href|action|formaction|poster)\s*=\s*["\']?)\s*(?:javascript|vbscript)\s*:/i',
        '$1blocked:',
        (string)$html
    );
    if ($neutralized === null) {
        $neutralized = (string)$html;
    }

    $neutralized2 = preg_replace(
        '/(\s+(?:href|src|xlink:href|action|formaction|poster)\s*=\s*["\']?)\s*data\s*:\s*text\/html/i',
        '$1blocked-data:',
        $neutralized
    );

    return ($neutralized2 === null) ? $neutralized : $neutralized2;
}

/**
 * post_content_deny_attributes()
 *
 * Attribute blacklist applied to post content. Shared by the admin editor
 * sanitization and the public single-post renderer so both surfaces stay in
 * sync (Rule 6 - no duplicated pipeline).
 *
 * @category function
 * @return array List of attribute names to deny
 */
function post_content_deny_attributes()
{
    return [
        'style', 'onclick', 'onerror', 'onload', 'onmouseover',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onkeydown',
        'onkeyup', 'onkeypress'
    ];
}