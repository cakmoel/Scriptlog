<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders the category (topic) archive page.
 *
 * Validates that the requested category exists in the database via
 * FrontHelper before rendering. Returns a 404 response when the
 * category is not found or the helper is unavailable.
 */
class CategoryHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new CategoryHandler.
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

        if (!$frontHelper || !method_exists($frontHelper, 'grabSimpleFrontTopic')) {
            $this->renderer->render404();
            return;
        }
        $topic = $frontHelper->grabSimpleFrontTopic($id);
        if (empty($topic['ID'])) {
            $this->renderer->render404();
            return;
        }
        $this->renderer->render('category');
    }
}
