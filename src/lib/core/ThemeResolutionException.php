<?php

namespace Scriptlog\Core;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Exception thrown when a theme or template cannot be resolved.
 *
 * This is a framework-level exception that signals a theme configuration
 * or filesystem problem. Callers at the controller / dispatcher layer
 * should catch it and fall back gracefully (e.g. to the default theme)
 * rather than letting the request fail hard.
 *
 * Factory methods are provided for the most common resolution failures:
 *
 * <code>
 *   throw ThemeResolutionException::notFound('custom-theme');
 *   throw ThemeResolutionException::missingTemplate('header.php');
 * </code>
 *
 * @psalm-suppress PossiblyUnusedMethod
 */

class ThemeResolutionException extends \RuntimeException
{
    /**
     * Create an exception for a theme that could not be resolved.
     *
     * @param string      $themeName The unresolvable theme directory name.
     * @param string|null $fallback  The fallback theme that will be used instead.
     * @return self
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function notFound(string $themeName, ?string $fallback = null): self
    {
        $message = "Theme directory '" . $themeName . "' could not be resolved";
        if ($fallback !== null) {
            $message .= "; falling back to '" . $fallback . "'";
        }
        return new self($message, 100);
    }

    /**
     * Create an exception for a required template file that does not exist.
     *
     * @param string $template Template filename (e.g. "header.php").
     * @param string $themeDir The theme directory that was searched.
     * @return self
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function missingTemplate(string $template, string $themeDir = ''): self
    {
        $message = "Required template '" . $template . "' not found";
        if ($themeDir !== '') {
            $message .= " in theme directory '" . $themeDir . "'";
        }
        return new self($message, 101);
    }

    /**
     * Create an exception for an invalid or malicious template name.
     *
     * @param string $template The rejected template name.
     * @return self
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function invalidTemplateName(string $template): self
    {
        return new self(
            "Invalid template name rejected: '" . $template . "'",
            102
        );
    }
}
