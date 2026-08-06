<?php
namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Sidebar view model.
 *
 * Carries the already-escaped aggregates the sidebar template renders:
 * latest posts, categories, archives and tags. Every nested value is escaped
 * once at construction time.
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 */
final class SidebarViewModel extends AbstractThemeViewModel
{
    /** @var PostViewModel[] */
    private $latestPosts = [];

    /** @var array<int, array{title:string|null,url:string,count:string|null}> */
    private $categories = [];

    /** @var array<int, array{label:string|null,url:string,count:string|null}> */
    private $archives = [];

    /** @var array<int, array{label:string|null,url:string}> */
    private $tags = [];

    /**
     * Build a sidebar view model.
     *
     * @param array<string, mixed> $row
     * @param callable(string):string $escape
     * @return static
     */
    public static function fromRow(array $row, callable $escape)
    {
        $self = new self();

        if (isset($row['latest_posts']) && is_array($row['latest_posts'])) {
            $self->latestPosts = array_map(function ($post) use ($escape) {
                return PostViewModel::fromRow($post, $escape);
            }, $row['latest_posts']);
        }

        if (isset($row['categories']) && is_array($row['categories'])) {
            $self->categories = array_map(function ($cat) use ($escape) {
                return [
                    'title' => self::safe($cat['topic_title'] ?? null, $escape),
                    'url'   => isset($cat['url']) ? $escape((string)$cat['url']) : '#',
                    'count' => self::safe($cat['count'] ?? null, $escape),
                ];
            }, $row['categories']);
        }

        if (isset($row['archives']) && is_array($row['archives'])) {
            $self->archives = array_map(function ($arc) use ($escape) {
                return [
                    'label' => self::safe($arc['label'] ?? null, $escape),
                    'url'   => isset($arc['url']) ? $escape((string)$arc['url']) : '#',
                    'count' => self::safe($arc['count'] ?? null, $escape),
                ];
            }, $row['archives']);
        }

        if (isset($row['tags']) && is_array($row['tags'])) {
            $self->tags = array_map(function ($tag) use ($escape) {
                return [
                    'label' => self::safe($tag['label'] ?? null, $escape),
                    'url'   => isset($tag['url']) ? $escape((string)$tag['url']) : '#',
                ];
            }, $row['tags']);
        }

        $self->values = [
            'search_action' => isset($row['search_action']) ? $escape((string)$row['search_action']) : '',
        ];

        return $self;
    }

    /**
     * Build a sidebar view model from already-prepared, already-safe values.
     *
     * Mirrors PostViewModel::fromPrepared(): nested values are escaped or
     * normalized exactly once at the boundary (prepare_sidebar()) and stored
     * verbatim. latest_posts must be an array of PostViewModel instances.
     *
     * @param array<string, mixed> $prepared Prepared sidebar aggregates
     * @return static
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes sidebar
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public static function fromPrepared(array $prepared)
    {
        $self = new self();

        if (isset($prepared['latest_posts']) && is_array($prepared['latest_posts'])) {
            $self->latestPosts = $prepared['latest_posts'];
        }

        if (isset($prepared['categories']) && is_array($prepared['categories'])) {
            $self->categories = $prepared['categories'];
        }

        if (isset($prepared['archives']) && is_array($prepared['archives'])) {
            $self->archives = $prepared['archives'];
        }

        if (isset($prepared['tags']) && is_array($prepared['tags'])) {
            $self->tags = $prepared['tags'];
        }

        $self->values = [
            'search_action' => isset($prepared['search_action']) ? (string)$prepared['search_action'] : '',
        ];

        return $self;
    }

    /**
     * @return PostViewModel[]
     *
     * @psalm-suppress PossiblyUnusedMethod -- public getter consumed by
     *                 public/themes sidebar templates, outside Psalm scan tree.
     */
    public function latestPosts(): array
    {
        return $this->latestPosts;
    }

    /**
     * @return array<int, array{title:string|null,url:string,count:string|null}>
     *
     * @psalm-suppress PossiblyUnusedMethod -- public getter consumed by
     *                 public/themes sidebar templates, outside Psalm scan tree.
     */
    public function categories(): array
    {
        return $this->categories;
    }

    /**
     * @return array<int, array{label:string|null,url:string,count:string|null}>
     *
     * @psalm-suppress PossiblyUnusedMethod -- public getter consumed by
     *                 public/themes sidebar templates, outside Psalm scan tree.
     */
    public function archives(): array
    {
        return $this->archives;
    }

    /**
     * @return array<int, array{label:string|null,url:string}>
     *
     * @psalm-suppress PossiblyUnusedMethod -- public getter consumed by
     *                 public/themes sidebar templates, outside Psalm scan tree.
     */
    public function tags(): array
    {
        return $this->tags;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod -- public getter consumed by
     *                 public/themes sidebar templates, outside Psalm scan tree.
     */
    public function searchAction(): string
    {
        return $this->values['search_action'] ?? '';
    }
}
