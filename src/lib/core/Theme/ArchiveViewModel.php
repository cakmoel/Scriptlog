<?php
namespace Scriptlog\Core\Theme;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Archive view model.
 *
 * Carries the already-escaped display fields for a monthly archive entry
 * (archive.php / archives.php).
 *
 * @category Theme
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 */
final class ArchiveViewModel extends AbstractThemeViewModel
{
    /**
     * Build an archive view model from a prepared row.
     *
     * Expects `url`, `label` and `count` keys (already composed by the model
     * layer) OR raw month/year fields that are combined here.
     *
     * @param array<string, mixed> $row
     * @param callable(string):string $escape
     * @return static
     */
    public static function fromRow(array $row, callable $escape)
    {
        $self = new self();

        $year = $self->safe($row['year'] ?? ($row['year_archive'] ?? null), $escape);
        $month = $self->safe($row['month'] ?? ($row['month_archive'] ?? null), $escape);

        $url = isset($row['url']) ? $escape((string)$row['url']) : '';
        $label = isset($row['label']) ? $escape((string)$row['label']) : '';

        $self->values = [
            'url'     => $url,
            'label'   => $label,
            'year'    => $year,
            'month'   => $month,
            'count'   => $self->safe($row['count'] ?? null, $escape),
        ];

        return $self;
    }

    /**
     * Build an archive view model from already-prepared, already-safe values.
     *
     * Mirrors PostViewModel::fromPrepared(): values are escaped exactly once
     * at the normalization boundary (prepare_archive()) and stored verbatim.
     *
     * @param array<string, mixed> $prepared Prepared archive fields
     * @return static
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes archive
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
    public function url(): string { return $this->values['url'] ?? ''; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function label(): ?string { return $this->values['label'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function year(): ?string { return $this->values['year'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function month(): ?string { return $this->values['month'] ?? null; }
    /** @psalm-suppress PossiblyUnusedMethod -- public getter consumed by public/themes */
    public function count(): ?string { return $this->values['count'] ?? null; }
}
