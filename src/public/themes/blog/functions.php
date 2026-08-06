<?php

/**
 * Blog Theme Functions
 *
 * Thin aggregator for the Bootstrap Blog theme helper files (Phase 5
 * remediation). Loading this file loads every theme helper via the
 * function_exists() guards kept inside each split file, preserving all
 * existing function names for backward compatibility.
 *
 * @category Theme Function
 * @package Scriptlog
 */

defined('SCRIPTLOG') || die('Direct access not permitted');

// Load the shared theme escaping boundary so every template in this theme
// uses the same escape helper (Phase 3 remediation). The helper now lives in
// lib/utility/ and is normally loaded by lib/utility-loader.php; the guard
// covers contexts where this file is loaded standalone.
if (!function_exists('theme_escape_html')) {
    require_once dirname(__DIR__, 3) . '/lib/utility/theme-escape.php';
}

// Load the shared ViewModel layer (Phase 2 remediation). prepare_post_card()
// now returns a PostViewModel, so the classes must be resolvable wherever this
// aggregator is loaded (normal bootstrap via composer autoload, or standalone).
if (!class_exists('Scriptlog\Core\Theme\PostViewModel', false)) {
    require_once dirname(__DIR__, 3) . '/lib/core/Theme/ThemeHelper.php';
    Scriptlog\Core\Theme\ThemeHelper::loadShared();
}

// Domain-specific helper modules (each keeps its own function_exists() guards).
require_once dirname(__FILE__) . '/functions-i18n.php';
require_once dirname(__FILE__) . '/functions-nav.php';
require_once dirname(__FILE__) . '/functions-post.php';
require_once dirname(__FILE__) . '/functions-media.php';
require_once dirname(__FILE__) . '/functions-comments.php';
