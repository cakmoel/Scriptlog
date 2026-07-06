<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class HomeHandler implements FrontRequestHandler
{
    private ThemeRendererInterface $renderer;

    public function __construct(ThemeRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(array $params): void
    {
        $this->renderer->render('home');
    }
}
