<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class DownloadHandler implements FrontRequestHandler
{
    private ThemeRenderer $renderer;
    private ?DownloadController $downloadController;

    public function __construct(
        ThemeRenderer $renderer,
        ?DownloadController $downloadController = null
    ) {
        $this->renderer = $renderer;
        $this->downloadController = $downloadController;
    }

    public function handle(array $params): void
    {
        $identifier = $params['value'] ?? '';
        if (empty($identifier)) {
            direct_page('', 302);
            return;
        }

        // Check if it's a file download request
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/file') !== false) {
            $identifier = preg_replace('#/file$#', '', $identifier);
            if ($this->downloadController) {
                $this->downloadController->download($identifier);
            }
            return;
        }

        // Render download page
        $GLOBALS['download_identifier'] = $identifier;
        $this->renderer->render('download');
    }
}
