<?php

namespace Scriptlog\Dao;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\Dao;

/**
 * LanguageDao
 *
 * Data Access Object for the `tbl_languages` table. Handles CRUD for the
 * supported languages (en, ar, zh, fr, ru, es, id), the active/default
 * language lookups, and language code uniqueness checks.
 *
 * @category  DAO Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 */
class LanguageDao extends Dao
{
    /**
     * Create a new language record.
     *
     * The `lang_code` value is normalized to lowercase before persistence.
     * Optional keys fall back to sensible defaults when omitted:
     * `lang_locale` (null), `lang_direction` ('ltr'),
     * `lang_sort` (0), `lang_is_default` (0), `lang_is_active` (1).
     *
     * @param array<string, mixed> $data Language attributes. Required keys:
     *        - `lang_code` (string)      Unique language code, e.g. 'en'
     *        - `lang_name` (string)      English language name, e.g. 'English'
     *        - `lang_native` (string)    Native language name, e.g. 'العربية'
     *        Optional keys:
     *        - `lang_locale` (string)    Locale string, e.g. 'en_US'
     *        - `lang_direction` (string) 'ltr' or 'rtl'
     *        - `lang_sort` (int)         Sort order
     *        - `lang_is_default` (int)   1 when this is the default language
     *        - `lang_is_active` (int)    1 when the language is active
     * @return int The ID of the newly created language record
     */
    public function createLanguage(array $data): int
    {
        $this->create($this->table('tbl_languages'), [
            'lang_code' => strtolower($data['lang_code']),
            'lang_name' => $data['lang_name'],
            'lang_native' => $data['lang_native'],
            'lang_locale' => $data['lang_locale'] ?? null,
            'lang_direction' => $data['lang_direction'] ?? 'ltr',
            'lang_sort' => $data['lang_sort'] ?? 0,
            'lang_is_default' => $data['lang_is_default'] ?? 0,
            'lang_is_active' => $data['lang_is_active'] ?? 1,
        ]);

        return $this->lastId();
    }

    /**
     * Find a language record by its primary key.
     *
     * @param int $id The language ID
     * @return array<string, mixed>|null The language row, or null when not found
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} WHERE ID = ?";
        $this->setSQL($sql);
        return $this->findRow([$id]) ?: null;
    }

    /**
     * Find a language record by its unique language code.
     *
     * @param string $code The language code, e.g. 'en' or 'ar'
     * @return array<string, mixed>|null The language row, or null when not found
     */
    public function findLanguageByCode(string $code): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} WHERE lang_code = ?";
        $this->setSQL($sql);
        return $this->findRow([$code]) ?: null;
    }

    /**
     * Find all active language records.
     *
     * Results are ordered by `lang_sort` ascending, then by `lang_name`
     * ascending. Only languages with `lang_is_active = 1` are returned.
     *
     * @return array<int, array<string, mixed>> List of active language rows
     */
    public function findActiveLanguages(): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} 
                WHERE lang_is_active = 1 
                ORDER BY lang_sort ASC, lang_name ASC";
        $this->setSQL($sql);
        return $this->findAll([]);
    }

    /**
     * Find the default language record.
     *
     * @return array<string, mixed>|null The language marked as default, or null when none exists
     */
    public function findDefaultLanguage(): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} WHERE lang_is_default = 1 LIMIT 1";
        $this->setSQL($sql);
        return $this->findRow([]) ?: null;
    }

    /**
     * Update an existing language record.
     *
     * Only the columns present in `$data` are modified; unknown keys are
     * ignored by the database wrapper.
     *
     * @param int   $id   The language ID to update
     * @param array<string, mixed> $data Language attributes to update
     * @return void
     */
    public function updateLanguage(int $id, array $data): void
    {
        $this->modify($this->table('tbl_languages'), $data, ['ID' => $id]);
    }

    /**
     * Mark a language as the site's default.
     *
     * This is a two-step operation: first the `lang_is_default` flag is
     * cleared on every language record, then it is set to 1 for the given
     * language ID, guaranteeing exactly one default at a time.
     *
     * @param int $id The language ID to promote to default
     * @return void
     */
    public function setDefaultLanguage(int $id): void
    {
        $this->dbc->dbQuery("UPDATE {$this->table('tbl_languages')} SET lang_is_default = 0");
        $this->modify($this->table('tbl_languages'), ['lang_is_default' => 1], ['ID' => $id]);
    }

    /**
     * Delete a language record by its primary key.
     *
     * @param int $id The language ID to delete
     * @return void
     */
    public function deleteLanguage(int $id): void
    {
        $this->deleteRecord($this->table('tbl_languages'), ['ID' => $id]);
    }

    /**
     * Count all language records.
     *
     * @return int The total number of languages
     */
    public function countLanguages(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table('tbl_languages')}";
        $this->setSQL($sql);
        return (int) $this->findColumn([]);
    }

    /**
     * Check whether a language code already exists.
     *
     * Useful for uniqueness validation before insert or update operations.
     *
     * @param string $code The language code to check, e.g. 'en'
     * @return bool True when the code already exists, false otherwise
     */
    public function codeExists(string $code): bool
    {
        $sql = "SELECT ID FROM {$this->table('tbl_languages')} WHERE lang_code = ?";
        $this->setSQL($sql);
        return $this->checkCountValue([$code]) > 0;
    }

    /**
     * Find all language records, active or not.
     *
     * Results are ordered by `lang_sort` ascending, then by `lang_name`
     * ascending. This is the admin-panel counterpart of `findActiveLanguages()`.
     *
     * @return array<int, array<string, mixed>> List of all language rows
     */
    public function findAllLanguages(): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} ORDER BY lang_sort ASC, lang_name ASC";
        $this->setSQL($sql);
        return $this->findAll([]);
    }
}
