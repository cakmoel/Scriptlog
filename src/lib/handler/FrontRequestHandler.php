<?php

namespace Scriptlog\Handler;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Contract for front-end request handlers.
 *
 * Each handler is responsible for a single route (home, post, page, etc.)
 * and receives parsed parameters from the dispatcher. Implementations
 * receive a ThemeRendererInterface instance to render the appropriate
 * theme template.
 */

interface FrontRequestHandler
{
    /**
     * Handle an incoming front-end request.
     *
     * Implementations should validate the parameters, check that the
     * requested content exists (if applicable), and render the appropriate
     * theme template via the ThemeRendererInterface instance.
     *
     * @param array $params Parsed route parameters (e.g. ['value' => '...']).
     * @return void
     */
    public function handle(array $params): void;
}
