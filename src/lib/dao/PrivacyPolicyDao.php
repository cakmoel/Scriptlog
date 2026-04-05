<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class PrivacyPolicyDao extends Dao
{
    public function createPolicy(array $data): int
    {
        $this->create($this->table('tbl_privacy_policies'), [
            'locale' => strtolower($data['locale']),
            'policy_title' => $data['policy_title'],
            'policy_content' => $data['policy_content'],
            'is_default' => $data['is_default'] ?? 0,
        ]);

        return $this->lastId();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_privacy_policies')} WHERE ID = ?";
        $this->setSQL($sql);
        return $this->findRow([$id]) ?: null;
    }

    public function findByLocale(string $locale): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_privacy_policies')} WHERE locale = ?";
        $this->setSQL($sql);
        return $this->findRow([$locale]) ?: null;
    }

    public function findDefault(): ?array
    {
        $sql = "SELECT * FROM {$this->table('tbl_privacy_policies')} WHERE is_default = 1 LIMIT 1";
        $this->setSQL($sql);
        return $this->findRow([]) ?: null;
    }

    public function findAllPolicies(): array
    {
        $sql = "SELECT * FROM {$this->table('tbl_privacy_policies')} ORDER BY locale ASC";
        $this->setSQL($sql);
        return $this->findAll([]);
    }

    public function updatePolicy(int $id, array $data): void
    {
        $this->modify($this->table('tbl_privacy_policies'), $data, ['ID' => $id]);
    }

    public function deletePolicy(int $id): void
    {
        $this->deleteRecord($this->table('tbl_privacy_policies'), ['ID' => $id]);
    }

    public function setDefaultPolicy(int $id): void
    {
        $this->dbc->dbQuery("UPDATE {$this->table('tbl_privacy_policies')} SET is_default = 0");
        $this->modify($this->table('tbl_privacy_policies'), ['is_default' => 1], ['ID' => $id]);
    }

    public function policyExists(string $locale): bool
    {
        $sql = "SELECT ID FROM {$this->table('tbl_privacy_policies')} WHERE locale = ?";
        $this->setSQL($sql);
        return $this->checkCountValue([$locale]) > 0;
    }
}
