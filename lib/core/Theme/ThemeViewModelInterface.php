<?php

namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Base contract for theme view models.
 *
 * A view model is an immutable, already-escaped value object that templates
 * print directly. It carries NO logic beyond read-only getters, so a template
 * can never trigger a database query or mutate shared state.
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 */
interface ThemeViewModelInterface
{
    /**
     * Return all escaped display values keyed by their public name.
     *
     * @psalm-suppress PossiblyUnusedMethod -- public getter consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array;
}
