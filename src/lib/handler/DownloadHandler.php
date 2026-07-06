<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders the download page or handles file download requests.
 *
 * When the URI contains a "/file" suffix the handler delegates to the
 * DownloadController to stream the file. Otherwise it renders the
 * download information page via the theme renderer.
 */
class DownloadHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Optional download controller for file streaming.
     *
     * @var DownloadController|null
     */
    private ?DownloadController $downloadController;

    /**
     * Construct a new DownloadHandler.
     *
     * @param ThemeRendererInterface $renderer             The theme renderer instance.
     * @param DownloadController|null $downloadController  Optional controller for file downloads.
     */
    public function __construct(
        ThemeRendererInterface $renderer,
        ?DownloadController $downloadController = null
    ) {
        $this->renderer = $renderer;
        $this->downloadController = $downloadController;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(array $params): void
    {
        $identifier = $params['value'] ?? '';
        if (empty($identifier)) {
            direct_page('', 302);
            return;
        }

        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/file') !== false) {
            $identifier = preg_replace('#/file$#', '', $identifier);
            if ($this->downloadController) {
                $this->downloadController->download($identifier);
            }
            return;
        }

        $GLOBALS['download_identifier'] = $identifier;
        $this->renderer->render('download');
    }
}
