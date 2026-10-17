<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
/** Prepared persistence for group membership and file associations. */
class sharingrepository extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback') { parent::init('tbl_workgroupfiles'); }
    public function group($id, $lock = false) {
        return $this->selectPrepared('SELECT * FROM tbl_workgroup WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id])[0] ?? null;
    }
    public function members($id) {
        return array_column($this->selectPrepared('SELECT userid FROM tbl_workgroup_users WHERE workgroupid = ? ORDER BY userid', [$id]), 'userid');
    }
    public function groups($context, $user, $manager) {
        return $this->selectPrepared('SELECT g.* FROM tbl_workgroup g WHERE g.contextcode = ?'
            . ($manager ? '' : ' AND EXISTS (SELECT 1 FROM tbl_workgroup_users m WHERE m.workgroupid = g.id AND m.userid = ?)')
            . ' ORDER BY g.description, g.id', $manager ? [$context] : [$context, $user]);
    }
    public function files($group) {
        return $this->selectPrepared('SELECT * FROM tbl_workgroupfiles WHERE workgroupid = ? ORDER BY datecreated, id', [$group]);
    }
    public function sharedFile($id, $lock = false) {
        return $this->selectPrepared('SELECT * FROM tbl_workgroupfiles WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id])[0] ?? null;
    }
    public function saveFile(array $row, $exists) {
        if ($exists) {
            if (!$this->update('id', $row['id'], $row, 'tbl_workgroupfiles')) throw new RuntimeException('write', 500);
        } elseif (!$this->insert($row, 'tbl_workgroupfiles')) throw new RuntimeException('write', 500);
    }
    public function removeFile($id, $group) {
        if ($this->executePrepared('DELETE FROM tbl_workgroupfiles WHERE id = ? AND workgroupid = ?', [$id, $group]) !== 1) throw new RuntimeException('conflict', 409);
    }
    public function saveGroup(array $row, array $members, $exists) {
        $ok = $exists ? $this->update('id', $row['id'], $row, 'tbl_workgroup') : $this->insert($row, 'tbl_workgroup');
        if (!$ok) throw new RuntimeException('write', 500);
        $this->executePrepared('DELETE FROM tbl_workgroup_users WHERE workgroupid = ?', [$row['id']]);
        foreach ($members as $user) if (!$this->insert(['workgroupid'=>$row['id'], 'contextcode'=>$row['contextcode'], 'userid'=>$user], 'tbl_workgroup_users')) throw new RuntimeException('write', 500);
    }
    public function removeGroup($id) {
        // Removing a group never deletes original File Manager files.
        $this->executePrepared('DELETE FROM tbl_workgroupfiles WHERE workgroupid = ?', [$id]);
        $this->executePrepared('DELETE FROM tbl_workgroup_users WHERE workgroupid = ?', [$id]);
        if ($this->executePrepared('DELETE FROM tbl_workgroup WHERE id = ?', [$id]) !== 1) throw new RuntimeException('conflict', 409);
    }
}
