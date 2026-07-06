<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders the homepage.
 *
 * Displays the front page of the blog. Rendering is delegated entirely
 * to the theme renderer; no parameter validation is required.
 */
class HomeHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new HomeHandler.
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
        $this->renderer->render('home');
    }
}
