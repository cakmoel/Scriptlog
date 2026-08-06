<?php
namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * ThemeViewModelFactory
 *
 * Turns prepared arrays from FrontService/models into typed, already-escaped
 * view models. All escaping flows through the single theme_escape_html()
 * boundary so every theme renders from the same safe data shape.
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 */
final class ThemeViewModelFactory
{
    /** @var callable(string):string */
    private $escape;

    /**
     * @param callable(string):string|null $escape Optional escaping callable.
     *        Defaults to the global theme_escape_html() boundary.
     */
    public function __construct(?callable $escape = null)
    {
        $this->escape = $escape ?: static function (string $value): string {
            return theme_escape_html($value);
        };
    }

    /**
     * Create a post view model.
     *
     * @param array<string, mixed> $row
     * @return PostViewModel
     */
    public function makePost(array $row): PostViewModel
    {
        return PostViewModel::fromRow($row, $this->escape);
    }

    /**
     * Create a post view model from already-prepared, already-safe values.
     *
     * Used by the theme card pipeline (prepare_post_card()): values are
     * escaped/sanitized exactly once at the normalization boundary, so the
     * factory is the single creation point for both raw rows and prepared
     * values.
     *
     * @param array<string, mixed> $prepared
     * @return PostViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes card
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public function makePostFromPrepared(array $prepared): PostViewModel
    {
        return PostViewModel::fromPrepared($prepared);
    }

    /**
     * Create a page view model.
     *
     * @param array<string, mixed> $row
     * @return PageViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function makePage(array $row): PageViewModel
    {
        return PageViewModel::fromRow($row, $this->escape);
    }

    /**
     * Create a page view model from already-prepared, already-safe values.
     *
     * @param array<string, mixed> $prepared
     * @return PageViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes page
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public function makePageFromPrepared(array $prepared): PageViewModel
    {
        return PageViewModel::fromPrepared($prepared);
    }

    /**
     * Create an archive view model.
     *
     * @param array<string, mixed> $row
     * @return ArchiveViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function makeArchive(array $row): ArchiveViewModel
    {
        return ArchiveViewModel::fromRow($row, $this->escape);
    }

    /**
     * Create an archive view model from already-prepared, already-safe values.
     *
     * @param array<string, mixed> $prepared
     * @return ArchiveViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes archive
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public function makeArchiveFromPrepared(array $prepared): ArchiveViewModel
    {
        return ArchiveViewModel::fromPrepared($prepared);
    }

    /**
     * Create a menu view model.
     *
     * @param array<string, mixed> $row
     * @return MenuViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function makeMenu(array $row): MenuViewModel
    {
        return MenuViewModel::fromRow($row, $this->escape);
    }

    /**
     * Create a menu view model from already-prepared, already-safe values.
     *
     * @param array<string, mixed> $prepared
     * @return MenuViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes nav
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public function makeMenuFromPrepared(array $prepared): MenuViewModel
    {
        return MenuViewModel::fromPrepared($prepared);
    }

    /**
     * Create a sidebar view model.
     *
     * @param array<string, mixed> $row
     * @return SidebarViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function makeSidebar(array $row): SidebarViewModel
    {
        return SidebarViewModel::fromRow($row, $this->escape);
    }

    /**
     * Create a sidebar view model from already-prepared, already-safe values.
     *
     * @param array<string, mixed> $prepared
     * @return SidebarViewModel
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes sidebar
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public function makeSidebarFromPrepared(array $prepared): SidebarViewModel
    {
        return SidebarViewModel::fromPrepared($prepared);
    }

    /**
     * Create a collection of post view models from a list of rows.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return PostViewModel[]
     *
     * @psalm-suppress PossiblyUnusedMethod -- public API consumed by
     *                 public/themes templates, outside the Psalm scan tree.
     */
    public function makePosts(array $rows): array
    {
        return array_map(function (array $row): PostViewModel {
            return $this->makePost($row);
        }, $rows);
    }
}
