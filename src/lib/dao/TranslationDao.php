<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class TranslationDao extends Dao
{
    public function createTranslation(array $data): int
    {
        $this->create($this->table('tbl_translations'), [
            'lang_id' => $data['lang_id'],
            'translation_key' => $data['translation_key'],
            'translation_value' => $data['translation_value'],
            'translation_context' => $data['translation_context'] ?? null,
            'translation_plurals' => $data['translation_plurals'] ?? null,
            'is_html' => $data['is_html'] ?? 0,
        ]);

        return $this->lastId();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} WHERE ID = ?";
        $this->setSQL($sql);
        return $this->findRow([$id]) ?: null;
    }

    public function findTranslationsByLocale(int $langId): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? 
                ORDER BY translation_key ASC";
        $this->setSQL($sql);
        return $this->findAll([$langId]);
    }

    public function findTranslationByKey(int $langId, string $key): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? AND translation_key = ?";
        $this->setSQL($sql);
        return $this->findRow([$langId, $key]) ?: null;
    }

    public function findByContext(int $langId, string $context): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? AND translation_context = ?
                ORDER BY translation_key ASC";
        $this->setSQL($sql);
        return $this->findAll([$langId, $context]);
    }

    public function search(int $langId, string $query): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? 
                AND (translation_key LIKE ? OR translation_value LIKE ?)
                ORDER BY translation_key ASC";
        $this->setSQL($sql);
        return $this->findAll([$langId, "%{$query}%", "%{$query}%"]);
    }

    public function updateTranslation(int $id, array $data): void
    {
        $this->modify($this->table('tbl_translations'), $data, ['ID' => $id]);
    }

    public function deleteTranslation(int $id): void
    {
        $this->deleteRecord($this->table('tbl_translations'), ['ID' => $id]);
    }

    public function deleteByLanguage(int $langId): void
    {
        $this->deleteRecord($this->table('tbl_translations'), ['lang_id' => $langId]);
    }

    public function exportToJson(int $langId): array
    {
        $translations = $this->findTranslationsByLocale($langId);
        $result = [];

        foreach ($translations as $t) {
            $result[$t['translation_key']] = $t['translation_value'];
        }

        return $result;
    }

    public function countByLanguage(int $langId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table('tbl_translations')} WHERE lang_id = ?";
        $this->setSQL($sql);
        return (int) $this->findColumn([$langId]);
    }

    public function getDistinctContexts(): array
    {
        $sql = "SELECT DISTINCT translation_context FROM {$this->table('tbl_translations')} 
                WHERE translation_context IS NOT NULL 
                ORDER BY translation_context";
        $this->setSQL($sql);
        return $this->findAll([]);
    }

    public function findByKeyPrefix(int $langId, string $prefix): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? AND translation_key LIKE ?
                ORDER BY translation_key ASC";
        $this->setSQL($sql);
        return $this->findAll([$langId, $prefix . '%']);
    }

    public function findByLocaleCode(string $localeCode): array
    {
        $sql = "SELECT t.translation_key, t.translation_value 
                FROM {$this->table('tbl_translations')} t
                JOIN {$this->table('tbl_languages')} l ON t.lang_id = l.ID
                WHERE l.lang_code = ?
                ORDER BY t.translation_key ASC";
        $this->setSQL($sql);
        return $this->findAll([$localeCode]);
    }

    public function getTranslationValue(int $langId, string $key): ?string
    {
        $sql = "SELECT translation_value FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? AND translation_key = ?
                LIMIT 1";
        $this->setSQL($sql);
        $result = $this->findRow([$langId, $key]);
        return $result['translation_value'] ?? null;
    }

    public function findTranslationsPaginated(int $langId, int $offset, int $limit): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? 
                ORDER BY translation_key ASC
                LIMIT ? OFFSET ?";
        $this->setSQL($sql);
        return $this->findAll([$langId, $limit, $offset]);
    }

    public function findByContextPaginated(int $langId, string $context, int $offset, int $limit): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? AND translation_context = ?
                ORDER BY translation_key ASC
                LIMIT ? OFFSET ?";
        $this->setSQL($sql);
        return $this->findAll([$langId, $context, $limit, $offset]);
    }

    public function searchPaginated(int $langId, string $query, int $offset, int $limit): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? 
                AND (translation_key LIKE ? OR translation_value LIKE ?)
                ORDER BY translation_key ASC
                LIMIT ? OFFSET ?";
        $this->setSQL($sql);
        return $this->findAll([$langId, "%{$query}%", "%{$query}%", $limit, $offset]);
    }

    public function countByContext(int $langId, string $context): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table('tbl_translations')} WHERE lang_id = ? AND translation_context = ?";
        $this->setSQL($sql);
        return (int) $this->findColumn([$langId, $context]);
    }

    public function countSearch(int $langId, string $query): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table('tbl_translations')} 
                WHERE lang_id = ? 
                AND (translation_key LIKE ? OR translation_value LIKE ?)";
        $this->setSQL($sql);
        return (int) $this->findColumn([$langId, "%{$query}%", "%{$query}%"]);
    }

    public function countAllTranslations(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table('tbl_translations')}";
        $this->setSQL($sql);
        return (int) $this->findColumn([]);
    }

    public function findAllTranslationsPaginated(int $offset, int $limit): array
    {
        $sql = "SELECT t.*, l.lang_code, l.lang_name 
                FROM {$this->table('tbl_translations')} t
                LEFT JOIN {$this->table('tbl_languages')} l ON t.lang_id = l.ID
                ORDER BY t.translation_key ASC
                LIMIT ? OFFSET ?";
        $this->setSQL($sql);
        return $this->findAll([$limit, $offset]);
    }
}
