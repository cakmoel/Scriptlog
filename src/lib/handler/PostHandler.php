<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class PostHandler implements FrontRequestHandler
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
