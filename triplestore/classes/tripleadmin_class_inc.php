<?php
/** Native Triple Store administration policy and writes. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class tripleadmin extends ChisimbaObject
{
    private $user;
    private $store;
    public function init()
    {
        $this->user = $this->getObject('user', 'security');
        $this->store = $this->getObject('dbtriplestore', 'triplestore');
    }
    public function mayManage(): bool
    {
        return $this->user->isLoggedIn() && $this->user->isAdmin();
    }
    public static function revision(array $row): string
    {
        return hash('sha256', serialize(array_map(fn($key) => (string) ($row[$key] ?? ''),
            ['id','subject','predicate','object','userid','date'])));
    }
    /** Validate without trimming stored identifiers or silently truncating content. */
    public static function validValues(array $values): bool
    {
        foreach (['subject'=>32, 'predicate'=>32, 'object'=>16384] as $field => $limit) {
            $value = $values[$field] ?? null;
            if (!is_string($value) || trim($value) === '' || !mb_check_encoding($value, 'UTF-8')
                || mb_strlen($value, 'UTF-8') > $limit || str_contains($value, "\0")) return false;
        }
        return true;
    }
    /** Lock and compare the displayed revision before changing an existing relationship. */
    public function write(string $id, array $values, string $revision, bool $delete = false): string
    {
        if (!$this->mayManage()) return 'forbidden';
        if (!$delete && !self::validValues($values)) return 'invalid';
        return $this->store->transaction(function () use ($id, $values, $revision, $delete) {
            if ($id !== '') {
                $row = $this->store->lockedTriple($id);
                if (!$row) return 'missing';
                if (!hash_equals(self::revision($row), $revision)) return 'conflict';
                $ok = $delete ? $this->store->delete($id)
                    : $this->store->update($id, $values['subject'], $values['predicate'], $values['object']);
            } elseif (!$delete) {
                $ok = $this->store->insert($values['subject'], $values['predicate'], $values['object']);
            } else return 'missing';
            if (!$ok) throw new RuntimeException('Triple write failed.');
            return 'saved';
        });
    }
}
