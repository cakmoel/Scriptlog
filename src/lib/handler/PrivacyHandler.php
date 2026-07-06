<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders the privacy policy page.
 *
 * Displays the site's privacy policy content. Rendering is delegated
 * entirely to the theme renderer; no parameter validation is required.
 */
class PrivacyHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new PrivacyHandler.
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
        $this->renderer->render('privacy');
    }
}
