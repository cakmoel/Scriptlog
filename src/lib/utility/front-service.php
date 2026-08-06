<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * front_service
 *
 * Retrieve the shared FrontService instance from the global registry.
 * Returns null when the service has not been registered (e.g., during
 * installation or in CLI contexts) so callers can fail safely instead of
 * reaching for the deprecated FrontHelper static facade.
 *
 * @category Function
 * @author   System
 * @license  MIT
 * @return Scriptlog\Service\FrontService|null
 */
function front_service()
{
    if (!class_exists('Registry') || !Registry::isKeySet('frontService')) {
        return null;
    }

    $service = Registry::get('frontService');

    return ($service instanceof \Scriptlog\Service\FrontService) ? $service : null;
}
