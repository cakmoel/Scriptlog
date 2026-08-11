<?php

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Single escaping boundary for theme output.
 *
 * This is the ONLY place theme templates escape dynamic text/attribute
 * values. It is deliberately plain: it performs an htmlspecialchars() pass
 * with ENT_QUOTES | ENT_SUBSTITUTE and a UTF-8 charset. It must NOT be
 * chained with safe_html()/escape_html() (see AGENTS.md § Output escaping
 * pitfall) — content that was already sanitized with paragraph_trim() is
 * passed through safe_html() instead, never through this helper.
 *
 * Kept as a function (not a method) so it works inside the procedural theme
 * templates and can be injected as a callable into the ViewModel factory.
 *
 * @param string $value Raw dynamic value to escape
 * @return string Escaped value safe for text/attribute context
 */
function theme_escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
