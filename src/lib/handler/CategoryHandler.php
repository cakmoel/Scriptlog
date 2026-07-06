<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class CategoryHandler implements FrontRequestHandler
{
    private ThemeRendererInterface $renderer;

    public function __construct(ThemeRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

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
