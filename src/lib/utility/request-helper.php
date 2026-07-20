<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Check if the current request is an HTMX request.
 *
 * @return bool
 */
function is_htmx_request(): bool
{
    return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
}

/**
 * Get the HTMX target element ID.
 *
 * @return string|null
 */
function htmx_target(): ?string
{
    return $_SERVER['HTTP_HX_TARGET'] ?? null;
}

/**
 * Get the HTMX trigger element ID.
 *
 * @return string|null
 */
function htmx_trigger(): ?string
{
    return $_SERVER['HTTP_HX_TRIGGER'] ?? null;
}

/**
 * Render an HTMX fragment from the active theme's partials directory.
 *
 * @param string $fragment
 * @param array $data
 * @param int $statusCode
 */
function render_htmx_fragment(string $fragment, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=utf-8');

    $activeTheme = function_exists('theme_identifier') ? theme_identifier() : null;

    if (is_array($activeTheme) && isset($activeTheme['theme_directory'])) {
        $themeDir = APP_ROOT . APP_THEME . DIRECTORY_SEPARATOR . $activeTheme['theme_directory'] . DIRECTORY_SEPARATOR;
    } else {
        $themeDir = APP_ROOT . APP_THEME . DIRECTORY_SEPARATOR . 'valdur' . DIRECTORY_SEPARATOR;
    }

    $partialPath = $themeDir . 'partials' . DIRECTORY_SEPARATOR . $fragment . '.php';

    if (!file_exists($partialPath)) {
        http_response_code(500);
        echo '<!-- Fragment not found: ' . htmlspecialchars($fragment, ENT_QUOTES, 'UTF-8') . ' -->';
        return;
    }

    if (!empty($data)) {
        extract($data);
    }

    require $partialPath;
}
