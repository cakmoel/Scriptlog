<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class PrivacyHandler implements FrontRequestHandler
{
    private ThemeRenderer $renderer;

    public function __construct(ThemeRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(array $params): void
    {
        $this->renderer->render('privacy');
    }
}
