<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders the monthly archive view.
 *
 * Receives a month/year value from the dispatcher and renders the
 * archive template. Redirects to the homepage if no value is provided.
 */
class ArchiveHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new ArchiveHandler.
     *
     * @param ThemeRendererInterface $renderer The theme renderer instance.
     */
    public function __construct(ThemeRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(array $params): void
    {
        $value = $params['value'] ?? '';

        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $this->renderer->render('archive');
    }
}
