<?php

namespace Scriptlog\Handler;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Renders a static page.
 *
 * Validates that the requested page exists in the database via the
 * shared FrontService before rendering. Returns a 404 response when the
 * page is not found or the service is unavailable.
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

        $frontService = HandleRequest::handleFrontHelper();

        if (!$frontService || !method_exists($frontService, 'getSimplePage')) {
            $this->renderer->render404();
            return;
        }
        $page = $frontService->getSimplePage($id);
        if (empty($page['ID'])) {
            $this->renderer->render404();
            return;
        }
        $this->renderer->render('page');
    }
}
