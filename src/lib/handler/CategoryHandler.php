<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class CategoryHandler implements FrontRequestHandler
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

        if ($frontHelper && method_exists($frontHelper, 'grabSimpleFrontTopic')) {
            $topic = $frontHelper->grabSimpleFrontTopic($id);
            if (empty($topic['ID'])) {
                $this->renderer->render404();
            } else {
                $this->renderer->render('category');
            }
        } else {
            $this->renderer->render404();
        }
    }
}
