<?php

namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * ThemeHelper facade
 *
 * Loads the shared theme view model classes once and exposes a lazily-created
 * ThemeViewModelFactory. Both the blog and valdur themes (and third-party
 * themes) require() this single file to get the same data shape and escaping
 * boundary.
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 *
 * @psalm-suppress UnusedClass -- public facade loaded by public/themes, which
 *                 are outside the Psalm scan tree (lib/ only).
 */
final class ThemeHelper
{
    /** @var ThemeViewModelFactory|null */
    private static $factory = null;

    /** @var bool Whether the shared classes have been loaded */
    private static $loaded = false;

    /**
     * Load the shared theme VM classes (idempotent).
     *
     * @return void
     */
    public static function loadShared(): void
    {
        if (self::$loaded) {
            return;
        }

        $dir = __DIR__ . '/';

        if (!function_exists('theme_escape_html')) {
            require_once dirname(__DIR__, 2) . '/utility/theme-escape.php';
        }

        $classes = [
            'ThemeViewModelInterface',
            'AbstractThemeViewModel',
            'PostViewModel',
            'PageViewModel',
            'ArchiveViewModel',
            'MenuViewModel',
            'SidebarViewModel',
            'ThemeViewModelFactory',
        ];

        foreach ($classes as $class) {
            if (!class_exists($class, false) && !interface_exists($class, false)) {
                require_once $dir . $class . '.php';
            }
        }

        self::$loaded = true;
    }

    /**
     * Get a shared ThemeViewModelFactory instance.
     *
     * @return ThemeViewModelFactory
     */
    public static function factory(): ThemeViewModelFactory
    {
        self::loadShared();

        if (self::$factory === null) {
            self::$factory = new ThemeViewModelFactory();
        }

        return self::$factory;
    }
}
