<?php
/** Persisted course membership and file ownership boundary. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class practiceaccess extends ChisimbaObject
{
    public function scope(): string
    {
        $user=$this->getObject('user','security');
        $code=(string)$this->getObject('dbcontext','context')->getContextCode();
        if (!$user->isLoggedIn() || $code==='' || $code==='root'
            || !$this->getObject('dbcontext','context')->getContext($code)
            || (!$user->lookupAdmin($user->userId()) && !$this->getObject('usercontext','context')->isContextMember($user->userId(),$code))) throw new DomainException('denied');
        return $code;
    }
    public function manager(): bool
    {
        $code=$this->scope(); $user=$this->getObject('user','security');
        return $user->lookupAdmin($user->userId()) || $user->isContextLecturer($user->userId(),$code);
    }
    public function requireManager(): void { if (!$this->manager()) throw new DomainException('denied'); }
    public function mayStoreFiles() { try { return $this->manager(); } catch (DomainException $e) { return false; } }
    public function mayDownloadFile($file,$resource)
    {
        try {
            $scope=$this->scope();
            $item=$this->getObject('practicebank','sightsandsounds')->item($scope,(string)$resource);
            return $item && $item['fileid']===($file['id']??'')
                && ($file['filefolder']??'')==='moduleassets/sightsandsounds'
                && ($this->manager() || $this->getObject('practicebank','sightsandsounds')->itemInPublishedActivity($scope,$item['id']));
        } catch (DomainException $e) { return false; }
    }
    public function csrf() { return $this->getObject('nativeauthwebcomposition','security')->build()['csrf']; }
    public function tokenField(): string
    {
        return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($this->csrf()->issue('sightsandsounds'),ENT_QUOTES,'UTF-8').'">';
    }
    public function mutation(string $method,string $token): void
    {
        $this->scope();
        if ($method!=='POST') throw new DomainException('method');
        if (!$this->csrf()->consume('sightsandsounds',$token)) throw new DomainException('csrf');
    }
}
