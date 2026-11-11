<?php
/** Persisted course membership and file ownership boundary. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class practiceaccess extends ChisimbaObject
{
    public function scope(): string
    {
        return $this->scopeFor((string)$this->getObject('dbcontext','context')->getContextCode());
    }
    public function scopeFor(string $code): string
    {
        $user=$this->getObject('user','security');
        if (!$user->isLoggedIn() || $code==='' || $code==='root'
            || !$this->getObject('dbcontext','context')->getContext($code)
            || (!$user->lookupAdmin($user->userId()) && !$this->getObject('usercontext','context')->isContextMember($user->userId(),$code))) throw new DomainException('denied');
        return $code;
    }
    public function allows(string $capability,string $scope,?string $owner=null): bool
    {
        $this->scopeFor($scope);$user=$this->getObject('user','security');
        $manager=$user->lookupAdmin($user->userId())||$user->isContextLecturer($user->userId(),$scope);
        return match($capability) {
            'manage_bank','publish','view_results','grant_attempts'=>$manager,
            'take','view_own_result'=>$owner===null||$owner===(string)$user->userId(),
            default=>false,
        };
    }
    public function requireCapability(string $capability,string $scope,?string $owner=null): void { if(!$this->allows($capability,$scope,$owner))throw new DomainException('denied'); }
    public function manager(): bool { return $this->allows('manage_bank',$this->scope()); }
    public function requireManager(): void { $this->requireCapability('manage_bank',$this->scope()); }
    public function mayStoreFiles() { try { return $this->manager(); } catch (DomainException $e) { return false; } }
    public function mayDownloadFile($file,$resource)
    {
        try {
            $scope=is_array($resource)?$this->scopeFor($resource['context']):$this->scope();
            $itemId=is_array($resource)?$resource['id']:(string)$resource;
            $item=$this->getObject('practicebank','sightsandsounds')->item($scope,$itemId);
            return $item && $item['fileid']===($file['id']??'')
                && ($file['filefolder']??'')==='moduleassets/sightsandsounds'
                && ((!is_array($resource) && $this->manager()) || $this->getObject('practicebank','sightsandsounds')->itemInPublishedActivity($scope,$item['id']));
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
