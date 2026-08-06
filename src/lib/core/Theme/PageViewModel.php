<?php
namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Page view model.
 *
 * Carries the already-escaped, display-ready fields for a static page
 * rendered by page.php.
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 */
final class PageViewModel extends AbstractThemeViewModel
{
    /**
     * Build a page view model from a prepared row.
     *
     * @param array<string, mixed> $row
     * @param callable(string):string $escape
     * @return static
     */
    public static function fromRow(array $row, callable $escape)
    {
        $self = new self();

        // URL composition belongs to the model/service layer. Prefer a
        // prepared 'url' value; never call permalinks() here (it would hit
        // the database from the view layer).
        $url = isset($row['url']) && is_string($row['url'])
            ? $row['url']
            : '';

        $self->values = [
            'id'            => $self::safe($row['ID'] ?? null, $escape),
            'title'         => $self::safe($row['post_title'] ?? null, $escape),
            'url'           => $escape($url),
            'slug'          => $self::safe($row['post_slug'] ?? null, $escape),
            'author'        => $self::safe($row['user_fullname'] ?? null, $escape),
            'date'          => $self::safe($row['post_date'] ?? null, $escape),
            'excerpt'       => $self::safe($row['post_summary'] ?? null, $escape),
            'content'       => $self::safe($row['post_content'] ?? null, $escape),
            'media'         => $self::safe($row['media_filename'] ?? null, $escape),
            'media_caption' => $self::safe($row['media_caption'] ?? null, $escape),
            'tags'          => $self::safe($row['post_tags'] ?? null, $escape),
        ];

        return $self;
    }

    /**
     * Build a page view model from already-prepared, already-safe values.
     *
     * Mirrors PostViewModel::fromPrepared(): the values are escaped or
     * sanitized exactly once at the normalization boundary (prepare_page()),
     * so they are stored verbatim with no second escaping pass. Trusted
     * sanitized HTML (content) must be passed as-is and printed as-is.
     *
     * @param array<string, mixed> $prepared Prepared page fields
     * @return static
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes page
     *                 pipeline, outside the Psalm scan tree (lib/ only).
     */
    public static function fromPrepared(array $prepared)
    {
        $self = new self();

        foreach ($prepared as $key => $value) {
            $self->values[$key] = ($value === null) ? null : (string)$value;
        }

        return $self;
    }

    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function id(): ?string { return $this->values['id'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function title(): ?string { return $this->values['title'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function url(): string { return $this->values['url'] ?? ''; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function slug(): ?string { return $this->values['slug'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function author(): ?string { return $this->values['author'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function date(): ?string { return $this->values['date'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function excerpt(): ?string { return $this->values['excerpt'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function content(): ?string { return $this->values['content'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function media(): ?string { return $this->values['media'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function mediaCaption(): ?string { return $this->values['media_caption'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function tags(): ?string { return $this->values['tags'] ?? null; }
}
