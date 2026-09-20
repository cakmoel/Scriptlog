<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * RateLimiter — PSR-4 backward-compatibility shim
 *
 * The class has been moved to Scriptlog\Core\RateLimiter.
 * This file loads the new class and creates a global alias
 * for code that still references the unnamespaced class.
 */

require_once __DIR__ . '/../core/RateLimiter.php';

if (!class_exists('RateLimiter', false)) {
    class_alias('Scriptlog\Core\RateLimiter', 'RateLimiter');
}
