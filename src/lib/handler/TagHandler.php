<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders the tag archive page.
 *
 * Displays posts matching the requested tag. Redirects to the homepage
 * if no tag value is provided.
 */
class TagHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new TagHandler.
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
        $tag = $params['value'] ?? '';

        if (empty($tag)) {
            direct_page('', 302);
            return;
        }

        $this->renderer->render('tag');
    }
}
