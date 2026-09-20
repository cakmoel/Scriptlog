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
 * decode twice, strip inline style attributes, then run htmLawed with the
 * event-handler/style blacklist.
 *
 * @category function
 * @param string $content Raw, entity-encoded post content
 * @return string Sanitized HTML safe to embed in the editor or template
 */
function sanitize_post_content($content)
{
    $decoded = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    $decoded = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');

    $clean = preg_replace('/\s*style="[^"]*"/', '', $decoded) ?? '';
    $clean = preg_replace('/\s*style=[^>\s]*/', '', $clean) ?? '';

    if (function_exists('htmLawed')) {
        return htmLawed($clean, array(
            'deny_attribute' => implode(',', post_content_deny_attributes()),
            'keep_bad' => 0
        ));
    }

    return $clean;
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