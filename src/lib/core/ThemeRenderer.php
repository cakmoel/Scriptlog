<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class ThemeRenderer
{
    private string $themeDir;

    public function __construct()
    {
        $theme = theme_identifier();
        $this->themeDir = APP_ROOT . APP_THEME
            . escape_html($theme['theme_directory'])
            . DIRECTORY_SEPARATOR;
    }

    public function render(string $template, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        $this->renderHeader();
        $this->renderContent($template);
        $this->renderFooter();
    }

    public function renderHeader(): void
    {
        include $this->themeDir . 'header.php';
    }

    public function renderContent(string $template): void
    {
        $file = $this->themeDir . basename($template) . '.php';
        if (!file_exists($file)) {
            scriptlog_error("Content template '$template' not found");
            return;
        }
        include $file;
    }

    public function renderFooter(): void
    {
        include $this->themeDir . 'footer.php';
    }

    public function render404(): void
    {
        http_response_code(404);
        $this->renderHeader();
        include $this->themeDir . '404.php';
        $this->renderFooter();
    }
}
