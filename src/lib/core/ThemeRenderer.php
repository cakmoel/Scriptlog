<?php

namespace Scriptlog\Core;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Default theme renderer implementation.
 *
 * Resolves the active theme directory from a given name, falls back to a
 * configured fallback theme when the primary theme is missing, and renders
 * header / content / footer templates via include statements.
 *
 * Usage:
 *
 * <code>
 *   // Constructor injection (preferred)
 *   $renderer = new ThemeRenderer('/var/www/public/themes/', 'my-theme');
 *
 *   // Global-context factory (legacy bootstrap)
 *   $renderer = ThemeRenderer::fromGlobalContext();
 *
 *   // Full-page render
 *   $renderer->render('single', 200);
 *
 *   // 404 response
 *   $renderer->render404();
 * </code>
 */

class ThemeRenderer implements ThemeRendererInterface
{
    /**
     * Default header template filename.
     */
    private const HEADER_TEMPLATE = 'header.php';

    /**
     * Default footer template filename.
     */
    private const FOOTER_TEMPLATE = 'footer.php';

    /**
     * Default 404 content template filename.
     */
    private const NOT_FOUND_TEMPLATE = '404.php';

    /**
     * Template file extension appended to content template names.
     */
    private const TEMPLATE_EXTENSION = '.php';

    /**
     * Fallback theme name used when the configured theme cannot be resolved.
     */
    private const FALLBACK_THEME = 'blog';

    /**
     * Resolved absolute path to the active theme directory.
     *
     * Always ends with a directory separator. Points to the fallback theme
     * when the configured theme is missing its required templates.
     *
     * @var string
     */
    private string $themeDir;

    /**
     * Optional callable for logging resolution errors.
     *
     * Signature: function (string $message): void
     *
     * @var callable|null
     */
    private $errorLogger;

    /**
     * Construct a new ThemeRenderer.
     *
     * @param string        $themesRootPath Absolute path to the parent directory
     *                                      containing all theme directories, e.g.
     *                                      "/var/www/public/themes/".
     * @param string        $themeName      Name of the active theme directory
     *                                      (sanitized with {@see basename()}).
     * @param callable|null $errorLogger    Optional error logger.
     * @param string        $fallbackTheme  Fallback theme name when the primary
     *                                      theme is missing required templates.
     */
    public function __construct(
        string $themesRootPath,
        string $themeName,
        ?callable $errorLogger = null,
        string $fallbackTheme = 'blog'
    ) {
        $themeName = basename($themeName);
        $themeName = $themeName !== '' ? $themeName : $fallbackTheme;

        $themeDir = $themesRootPath . $themeName . DIRECTORY_SEPARATOR;

        if (!file_exists($themeDir . self::HEADER_TEMPLATE)) {
            $themeDir = $themesRootPath . $fallbackTheme . DIRECTORY_SEPARATOR;
        }

        $this->themeDir = $themeDir;
        $this->errorLogger = $errorLogger;
    }

    /**
     * Factory method that constructs a ThemeRenderer from global application context.
     *
     * Uses {@see theme_identifier()} to determine the active theme and the
     * {@see APP_ROOT} / {@see APP_THEME} constants for the root path.
     * The default error logger is {@see scriptlog_error()}.
     *
     * @return self
     */
    public static function fromGlobalContext(): self
    {
        $theme = theme_identifier();

        $themeName = (is_array($theme) && isset($theme['theme_directory']))
            ? basename((string)$theme['theme_directory'])
            : '';

        return new self(
            APP_ROOT . APP_THEME,
            $themeName,
            'scriptlog_error'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $template, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        $this->renderHeader();
        $this->renderContent($template);
        $this->renderFooter();
    }

    /**
     * {@inheritDoc}
     */
    public function renderHeader(): void
    {
        $file = $this->themeDir . self::HEADER_TEMPLATE;
        if (!file_exists($file)) {
            $this->log(
                'Theme header template missing: ' . $file
                . ' (theme: ' . $this->themeDir . ')'
            );
            return;
        }
        include $file;
    }

    /**
     * {@inheritDoc}
     */
    public function renderContent(string $template): void
    {
        $safeName = basename($template);
        if ($safeName === '') {
            $this->log('Empty content template name (theme: ' . $this->themeDir . ')');
            return;
        }
        $file = $this->themeDir . $safeName . self::TEMPLATE_EXTENSION;
        if (!file_exists($file)) {
            $this->log(
                "Content template '" . $template . "' not found in "
                . $this->themeDir
            );
            return;
        }
        include $file;
    }

    /**
     * {@inheritDoc}
     */
    public function renderFooter(): void
    {
        $file = $this->themeDir . self::FOOTER_TEMPLATE;
        if (!file_exists($file)) {
            $this->log(
                'Theme footer template missing: ' . $file
                . ' (theme: ' . $this->themeDir . ')'
            );
            return;
        }
        include $file;
    }

    /**
     * {@inheritDoc}
     */
    public function render404(): void
    {
        http_response_code(404);
        $this->renderHeader();

        $file = $this->themeDir . self::NOT_FOUND_TEMPLATE;
        if (!file_exists($file)) {
            $this->log('404 template not found: ' . $file);
            return;
        }

        include $file;

        $this->renderFooter();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getThemeDir(): string
    {
        return $this->themeDir;
    }

    /**
     * Log a message via the configured error logger, if one is set.
     *
     * @param string $message The log message.
     */
    private function log(string $message): void
    {
        if ($this->errorLogger !== null) {
            call_user_func($this->errorLogger, $message);
        }
    }
}
