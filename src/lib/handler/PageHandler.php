<?php

namespace Scriptlog\Handler;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders a static page.
 *
 * Validates that the requested page exists in the database via
 * FrontHelper before rendering. Returns a 404 response when the
 * page is not found or the helper is unavailable.
 */

use Scriptlog\Core\HandleRequest;
use Scriptlog\Core\ThemeRendererInterface;

class PageHandler implements FrontRequestHandler
{
    /**
     * The theme renderer used to output the response.
     *
     * @var ThemeRendererInterface
     */
    private ThemeRendererInterface $renderer;

    /**
     * Construct a new PageHandler.
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
