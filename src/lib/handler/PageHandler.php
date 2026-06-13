<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class PageHandler implements FrontRequestHandler
{
    private ThemeRenderer $renderer;

    public function __construct(ThemeRenderer $renderer)
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

        if ($frontHelper && method_exists($frontHelper, 'grabSimpleFrontPage')) {
            $page = $frontHelper->grabSimpleFrontPage($id);
            if (empty($page['ID'])) {
                $this->renderer->render404();
            } else {
                $this->renderer->render('page');
            }
        } else {
            $this->renderer->render404();
        }
    }
}
