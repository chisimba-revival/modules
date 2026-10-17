<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
/** Current persisted authorisation; sharing never widens File Manager access. */
class sharingservice extends ChisimbaObject
{
    public $repo;
    public $user;
    public function init() {
        $this->repo = $this->getObject('sharingrepository', 'workgroup');
        $this->user = $this->getObject('user', 'security');
    }
    public function uid() { return $this->user->isLoggedIn() ? (string)$this->user->userId() : ''; }
    public function context() {
        $context = $this->getObject('dbcontext', 'context')->getContextCode();
        if (!$this->uid() || !is_string($context) || $context === ''
            || !$this->getObject('dbcontext', 'context')->getContext($context)) throw new RuntimeException('context', 403);
        return $context;
    }
    public function manager($context) {
        return $this->uid() !== '' && ($this->user->lookupAdmin($this->uid()) || $this->user->isContextLecturer($this->uid(), $context));
    }
    public function courseMember($context) {
        return $this->manager($context) || $this->getObject('usercontext', 'context')->isContextMember($this->uid(), $context);
    }
    public function group($id, $lock = false) {
        $context = $this->context();
        $row = is_string($id) ? $this->repo->group($id, $lock) : null;
        if (!$row || $row['contextcode'] !== $context) throw new RuntimeException('access', 403);
        if (!$this->courseMember($context) || (!$this->manager($context) && !in_array($this->uid(), $this->repo->members($id), true))) throw new RuntimeException('access', 403);
        return $row;
    }
    public function groups() {
        $context = $this->context();
        if (!$this->courseMember($context)) throw new RuntimeException('access', 403);
        return $this->repo->groups($context, $this->uid(), $this->manager($context));
    }
    public function mayEdit(array $row) {
        return $this->manager($this->context()) || (string)$row['modifierid'] === $this->uid();
    }
    public static function revision(array $row) { ksort($row); return hash('sha256', json_encode($row)); }
    public function file($id, $group, $editing = false) {
        $this->group($group);
        $row = $this->repo->sharedFile($id);
        if (!$row || $row['workgroupid'] !== $group) throw new RuntimeException('missing', 404);
        if ($editing && !$this->mayEdit($row)) throw new RuntimeException('access', 403);
        return $row;
    }
    public function readableFile($id) {
        $file = $this->getObject('dbfile','filemanager')->getFileInfo($id);
        return $file && $this->getObject('filereadpolicy','filemanager')->mayRead($file) ? $file : null;
    }
    public function visibleFiles($group) {
        $this->group($group);
        $rows = [];
        foreach ($this->repo->files($group) as $row) {
            $file = $this->readableFile($row['fileid']);
            // An owner/manager may remove an association whose original has gone.
            if (!$file && !$this->mayEdit($row)) continue;
            $row['file'] = $file;
            $row['editable'] = $this->mayEdit($row);
            $rows[] = $row;
        }
        return $rows;
    }
    public function saveFile($group, array $input, $editing) {
        return $this->repo->transactionWithSavepoint(function () use ($group, $input, $editing) {
            $this->group($group, true);
            $id = $this->text($input, 'id', 32, true);
            $old = $this->repo->sharedFile($id, true);
            if ($editing) {
                if (!$old || $old['workgroupid'] !== $group || !$this->mayEdit($old)) throw new RuntimeException('access', 403);
                if (!hash_equals(self::revision($old), is_string($input['revision'] ?? null) ? $input['revision'] : '')) throw new RuntimeException('conflict', 409);
            } elseif (!preg_match('/^[a-f0-9]{32}$/D', $id)) throw new RuntimeException('invalid', 422);
            $fields = ['fileid'=>$this->text($input,'fileupload',32,true), 'title'=>$this->text($input,'title',255,true),
                'description'=>$this->text($input,'description',255), 'version'=>$this->text($input,'version',32)];
            $file = $this->readableFile($fields['fileid']);
            if (!$file || (!$this->manager($this->context()) && (string)$file['userid'] !== $this->uid())) throw new RuntimeException('fileaccess', 403);
            if (!$editing && $old) {
                if ($old['workgroupid'] === $group && $old['modifierid'] === $this->uid()
                    && array_intersect_key($old,$fields) == $fields) return $id; // Idempotent create retry.
                throw new RuntimeException('conflict',409);
            }
            // modifierid is the legacy association owner; retain it when staff edit.
            $row = array_merge($fields,['id'=>$id,'workgroupid'=>$group,'modifierid'=>$old['modifierid'] ?? $this->uid(),
                'datecreated'=>$old['datecreated'] ?? date('Y-m-d'),'datemodified'=>date('Y-m-d')]);
            $this->repo->saveFile($row, $editing);
            return $id;
        });
    }
    public function deleteFile($group, $id, $revision) {
        $this->repo->transactionWithSavepoint(function () use ($group,$id,$revision) {
            $this->group($group,true);
            $row=$this->repo->sharedFile($id,true);
            if (!$row || $row['workgroupid']!==$group || !$this->mayEdit($row)) throw new RuntimeException('access',403);
            if (!hash_equals(self::revision($row),$revision)) throw new RuntimeException('conflict',409);
            $this->repo->removeFile($id,$group);
        });
    }
    public function candidates() {
        $context=$this->context();
        if (!$this->manager($context)) throw new RuntimeException('access',403);
        $users=$this->getObject('usercontext','context');$result=[];
        foreach (array_merge($users->getContextLecturers($context),$users->getContextStudents($context),$users->getContextGuests($context)) as $row) {
            $id=$row['userid'] ?? $row['userId'] ?? '';
            if ($id!=='') $result[$id]=trim($row['firstname'].' '.$row['surname']);
        }
        asort($result);return $result;
    }
    public function groupRevision(array $row) { $row['members']=$this->repo->members($row['id']);return self::revision($row); }
    public function saveGroup(array $input, $creating) {
        $context=$this->context();
        if (!$this->manager($context)) throw new RuntimeException('access',403);
        return $this->repo->transactionWithSavepoint(function () use ($input,$creating,$context) {
            if (!$this->manager($context)) throw new RuntimeException('access',403);
            $id=$this->text($input,'id',32,true);$old=$this->repo->group($id,true);
            if ($creating && !preg_match('/^[a-f0-9]{32}$/D',$id)) throw new RuntimeException('invalid',422);
            if (!$creating && (!$old || $old['contextcode']!==$context)) throw new RuntimeException('access',403);
            if (!$creating && !hash_equals($this->groupRevision($old),is_string($input['revision']??null)?$input['revision']:'')) throw new RuntimeException('conflict',409);
            $description=$this->text($input,'description',100,true);
            $members=$input['members']??[];
            if (!is_array($members) || count($members)>2000) throw new RuntimeException('invalid',422);
            $valid=$this->candidates();
            foreach ($members as $member) if (!is_string($member)||!array_key_exists($member,$valid)) throw new RuntimeException('invalid',422);
            $members=array_values(array_unique($members));sort($members);
            if ($creating && $old) {
                if ($old['contextcode']===$context && $old['creatorid']===$this->uid() && $old['description']===$description && $this->repo->members($id)===$members) return $id;
                throw new RuntimeException('conflict',409);
            }
            $this->repo->saveGroup(['id'=>$id,'contextcode'=>$context,'description'=>$description,
                'creatorid'=>$old['creatorid']??$this->uid(),'modifierid'=>$this->uid(),
                'datecreated'=>$old['datecreated']??date('Y-m-d'),'datemodified'=>date('Y-m-d')],$members,!$creating);
            return $id;
        });
    }
    public function deleteGroup($id,$revision) {
        $this->repo->transactionWithSavepoint(function () use ($id,$revision) {
            $row=$this->group($id,true);
            if (!$this->manager($row['contextcode'])) throw new RuntimeException('access',403);
            if (!hash_equals($this->groupRevision($row),$revision)) throw new RuntimeException('conflict',409);
            $this->repo->removeGroup($id);
        });
    }
    private function text(array $input,$key,$limit,$required=false) {
        $value=$input[$key]??'';
        if (!is_string($value)||mb_strlen($value)>$limit||($required&&trim($value)==='')) throw new RuntimeException('invalid',422);
        return trim($value);
    }
}
