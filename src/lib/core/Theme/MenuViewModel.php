<?php
namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Menu view model.
 *
 * Carries the already-escaped display fields for a single navigation menu
 * item. Templates print getters directly; the recursive tree structure is
 * preserved via the `children` list.
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 */
final class MenuViewModel extends AbstractThemeViewModel
{
    /** @var MenuViewModel[] */
    private $children = [];

    /**
     * Build a menu view model from a row.
     *
     * @param array<string, mixed> $row
     * @param callable(string):string $escape
     * @return static
     */
    public static function fromRow(array $row, callable $escape)
    {
        $self = new self();

        $self->values = [
            'id'        => $self->safe($row['ID'] ?? ($row['menu_id'] ?? null), $escape),
            'label'     => $self->safe($row['menu_label'] ?? null, $escape),
            'url'       => isset($row['menu_link']) ? $escape((string)$row['menu_link']) : '#',
            'parent'    => $self->safe($row['parent_id'] ?? null, $escape),
            'visibility'=> $self->safe($row['menu_visibility'] ?? 'public', $escape),
        ];

        return $self;
    }

    /**
     * Build a menu view model from already-prepared, already-safe values.
     *
     * Mirrors PostViewModel::fromPrepared(): values are escaped exactly once
     * at the normalization boundary (front_navigation() / prepare_menu()) and
     * stored verbatim. Children are attached afterwards via setChildren().
     *
     * @param array<string, mixed> $prepared Prepared menu fields
     * @return static
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes nav
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public static function fromPrepared(array $prepared)
    {
        $self = new self();

        foreach ($prepared as $key => $value) {
            $self->values[$key] = ($value === null) ? null : (string)$value;
        }

        if (!isset($self->values['url']) || $self->values['url'] === '') {
            $self->values['url'] = '#';
        }

        return $self;
    }

    /**
     * Attach child menu items (recursive tree).
     *
     * @param MenuViewModel[] $children
     * @return void
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function id(): ?string { return $this->values['id'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function label(): ?string { return $this->values['label'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function url(): string { return $this->values['url'] ?? '#'; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function parent(): ?string { return $this->values['parent'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function visibility(): ?string { return $this->values['visibility'] ?? null; }

    /**
     * @return MenuViewModel[]
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * @return bool Whether the item has child items
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }
}
