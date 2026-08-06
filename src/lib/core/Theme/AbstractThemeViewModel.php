<?php
namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Base implementation shared by all theme view models.
 *
 * Provides a single `fromRow()` contract and the escaped-value storage. All
 * values are escaped once, at construction time, through a callable passed in
 * by the factory. Subclasses only declare their public getters.
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 */
abstract class AbstractThemeViewModel implements ThemeViewModelInterface
{
    /** @var array<string, string|null> */
    protected $values = [];

    /**
     * Build the view model from a prepared row, escaping each value once.
     *
     * @param array<string, mixed> $row The prepared row from FrontService/model
     * @param callable(string):string $escape Single escaping boundary
     * @return static
     */
    abstract public static function fromRow(array $row, callable $escape);

    /**
     * Return the escaped value for a given key.
     *
     * @psalm-suppress PossiblyUnusedMethod -- public getter consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->values[$key] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Escape a single raw value through the provided boundary.
     *
     * @param mixed $raw
     * @param callable $escape
     * @return string|null
     */
    protected static function safe($raw, callable $escape): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return $escape((string)$raw);
    }
}
