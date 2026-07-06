<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class ArchiveHandler implements FrontRequestHandler
{
    private ThemeRendererInterface $renderer;

    public function __construct(ThemeRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(array $params): void
    {
        $value = $params['value'] ?? '';

        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $this->renderer->render('archive');
    }
}
