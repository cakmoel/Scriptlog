<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class LanguageDao extends Dao
{
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

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} WHERE ID = ?";
        $this->setSQL($sql);
        return $this->findRow([$id]) ?: null;
    }

    public function findLanguageByCode(string $code): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} WHERE lang_code = ?";
        $this->setSQL($sql);
        return $this->findRow([$code]) ?: null;
    }

    public function findActiveLanguages(): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} 
                WHERE lang_is_active = 1 
                ORDER BY lang_sort ASC, lang_name ASC";
        $this->setSQL($sql);
        return $this->findAll([]);
    }

    public function findDefaultLanguage(): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} WHERE lang_is_default = 1 LIMIT 1";
        $this->setSQL($sql);
        return $this->findRow([]) ?: null;
    }

    public function updateLanguage(int $id, array $data): void
    {
        $this->modify($this->table('tbl_languages'), $data, ['ID' => $id]);
    }

    public function setDefaultLanguage(int $id): void
    {
        $this->dbc->dbQuery("UPDATE {$this->table('tbl_languages')} SET lang_is_default = 0");
        $this->modify($this->table('tbl_languages'), ['lang_is_default' => 1], ['ID' => $id]);
    }

    public function deleteLanguage(int $id): void
    {
        $this->deleteRecord($this->table('tbl_languages'), ['ID' => $id]);
    }

    public function countLanguages(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table('tbl_languages')}";
        $this->setSQL($sql);
        return (int) $this->findColumn([]);
    }

    public function codeExists(string $code): bool
    {
        $sql = "SELECT ID FROM {$this->table('tbl_languages')} WHERE lang_code = ?";
        $this->setSQL($sql);
        return $this->checkCountValue([$code]) > 0;
    }

    public function findAllLanguages(): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_languages')} ORDER BY lang_sort ASC, lang_name ASC";
        $this->setSQL($sql);
        return $this->findAll([]);
    }
}
