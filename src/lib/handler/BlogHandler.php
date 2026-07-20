<?php

namespace Scriptlog\Handler;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders the blog listing page.
 *
 * Displays paginated blog posts. Rendering is delegated entirely
 * to the theme renderer; no parameter validation is required.
 */

use Scriptlog\Core\ThemeRendererInterface;

class BlogHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new BlogHandler.
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
        $this->renderer->render('blog');
    }
}
