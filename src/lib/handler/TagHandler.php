<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class TagHandler implements FrontRequestHandler
{
    private ThemeRendererInterface $renderer;

    public function __construct(ThemeRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

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
