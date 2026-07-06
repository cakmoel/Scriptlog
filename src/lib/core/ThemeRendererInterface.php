<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Contract for theme rendering in the application.
 *
 * Implementations are responsible for locating the active theme directory,
 * resolving fallback themes when the primary is missing, and rendering
 * header / content / footer templates in the correct sequence.
 *
 * Each rendering method outputs HTML directly via include statements.
 * Callers must not assume output buffering is active.
 */
interface ThemeRendererInterface
{
    /**
     * Render a full page: header, content template, and footer.
     *
     * Sets the HTTP response code before any output is sent.
     *
     * @param string $template   Content template name (without .php extension).
     * @param int    $statusCode HTTP status code (default 200).
     */
    public function render(string $template, int $statusCode = 200): void;

    /**
     * Include the current theme's header template.
     *
     * Outputs the opening HTML structure (doctype, head, navigation).
     * Called internally by {@see render()}.
     */
    public function renderHeader(): void;

    /**
     * Include a content template from the current theme directory.
     *
     * The template name is sanitized with {@see basename()} before inclusion
     * to prevent directory traversal attacks.
     *
     * @param string $template Content template name (without .php extension).
     */
    public function renderContent(string $template): void;

    /**
     * Include the current theme's footer template.
     */
    public function renderFooter(): void;

    /**
     * Render a 404 response.
     *
     * Sets a 404 HTTP status code, then renders the header, the 404 content
     * template, and the footer. If the 404 template does not exist a warning
     * is logged and rendering continues silently.
     */
    public function render404(): void;

    /**
     * Return the resolved absolute path to the active theme directory.
     *
     * The path always ends with a directory separator.
     *
     * @return string Absolute path to the theme directory, e.g.
     *                "/var/www/public/themes/blog/"
     */
    public function getThemeDir(): string;
}
