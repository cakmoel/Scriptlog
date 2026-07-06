<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class PageHandler implements FrontRequestHandler
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

        if (!$frontHelper || !method_exists($frontHelper, 'grabSimpleFrontPage')) {
            $this->renderer->render404();
            return;
        }
        $page = $frontHelper->grabSimpleFrontPage($id);
        if (empty($page['ID'])) {
            $this->renderer->render404();
            return;
        }
        $this->renderer->render('page');
    }
}
