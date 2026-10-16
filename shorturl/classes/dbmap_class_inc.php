<?php
/** ShortURL persistence. Original module: Charl Mert / AVOIR, GNU GPL. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbmap extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = "globalPearErrorCallback")
    {
        parent::init('tbl_shorturl_map', $pearDb, $errorCallback);
    }
    public function listMappings(): array
    {
        return $this->selectPrepared('SELECT * FROM tbl_shorturl_map ORDER BY ordering, id');
    }
    public function findMapping(string $id): ?array
    {
        return $this->selectPrepared('SELECT * FROM tbl_shorturl_map WHERE id = ?', [$id])[0] ?? null;
    }
    public function revision(array $row): string
    {
        ksort($row);
        return hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
    }
    private function editable(string $id, string $revision): array
    {
        $row = $this->findMapping($id);
        if (!$row || !hash_equals($this->revision($row), $revision)) throw new DomainException('stale');
        if ((int)$row['is_dynamic'] !== 0) throw new DomainException('dynamic');
        return $row;
    }
    public function saveMapping(array $input): array
    {
        $id = $input['id'] ?? '';
        $match = $input['txtMatchUrl'] ?? '';
        $target = $input['txtTargetUrl'] ?? '';
        if (str_starts_with($target, 'www.')) $target = 'http://'.$target;
        if ($match === '' || strlen($match)>255 || preg_match('/[\x00-\x20\x7f]/', $match)
            || strlen($target)>255 || !self::safeTarget($target)
            || !preg_match('/^[0-9]{1,9}$/D', $input['ordering'] ?? '0')) throw new DomainException('invalid');
        return $this->withDatabaseLock('shorturl-edit', function () use ($input,$id,$match,$target) {
            return $this->transactionWithSavepoint(function () use ($input,$id,$match,$target) {
                if ($id !== '') $this->editable($id, $input['revision'] ?? '');
                if ($this->selectPrepared('SELECT id FROM tbl_shorturl_map WHERE BINARY match_url = BINARY ?'.($id !== '' ? ' AND id <> ?' : ''), $id !== '' ? [$match,$id] : [$match])) throw new DomainException('duplicate');
                $values=['match_url'=>$match,'target_url'=>$target,'ordering'=>(int)($input['ordering']??0),'datestamp'=>$this->now()];
                if ($id !== '') {
                    if ($this->update('id',$id,$values) === false) throw new RuntimeException('Save failed');
                } else {
                    $id=$this->insert($values+['is_dynamic'=>0,'key_id'=>'']);
                    if (!$id) throw new RuntimeException('Insert failed');
                }
                $row=$this->findMapping($id);
                if (!$row) throw new RuntimeException('Saved row missing');
                return ['id'=>$id,'revision'=>$this->revision($row)];
            });
        });
    }
    public function deleteMapping(string $id, string $revision): void
    {
        $this->withDatabaseLock('shorturl-edit', function () use ($id,$revision) {
            $this->transactionWithSavepoint(function () use ($id,$revision) {
                $this->editable($id,$revision);
                if ($this->executePrepared('DELETE FROM tbl_shorturl_key WHERE map_id = ?',[$id]) === false) throw new RuntimeException('Key deletion failed');
                if (!$this->delete('id',$id)) throw new RuntimeException('Mapping deletion failed');
            });
        });
    }
    public static function safeTarget(string $url): bool
    {
        return !preg_match('/[\x00-\x20\x7f]/',$url) && filter_var($url,FILTER_VALIDATE_URL)
            && in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)
            && parse_url($url,PHP_URL_USER) === null && parse_url($url,PHP_URL_PASS) === null;
    }
    public function getTarget(string $request): ?string
    {
        $row=$this->selectPrepared('SELECT target_url FROM tbl_shorturl_map WHERE is_dynamic = 0 AND BINARY match_url = BINARY ? ORDER BY ordering, id LIMIT 1',[$request])[0]??null;
        return $row && self::safeTarget((string)$row['target_url']) ? $row['target_url'] : null;
    }
}
