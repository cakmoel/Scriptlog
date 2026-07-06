<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders a single post view.
 *
 * Validates that the requested post exists in the database via
 * FrontHelper before rendering. Returns a 404 response when the
 * post is not found or the helper is unavailable.
 */
class PostHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new PostHandler.
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
        $id = $params['value'] ?? '';

        if (empty($id)) {
            direct_page('', 302);
            return;
        }

        $frontHelper = class_exists('FrontHelper')
            ? HandleRequest::handleFrontHelper()
            : null;

        if (!$frontHelper || !method_exists($frontHelper, 'grabSimpleFrontPost')) {
            $this->renderer->render404();
            return;
        }
        $post = $frontHelper->grabSimpleFrontPost($id);
        if (empty($post['ID'])) {
            $this->renderer->render404();
            return;
        }
        $this->renderer->render('single');
    }
}
