<?php
/** Canonical organiser permissions and private ticket capabilities.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class eventpolicy extends ChisimbaObject
{
    public function canCreate()
    {
        $user=$this->getObject('user','security');
        if(!$user->isLoggedIn()) return false;
        if($user->isAdmin()) return true;
        $p=$this->getObject('permissionservice','security');
        $area=$p->areaIdForName('chisimba','events'); $right=$area?$p->rightIdForArea($area,'manage'):null;
        return (bool)($right&&$p->isGranted($user->userId(),$right));
    }
    public function canManage(array $event)
    {
        $u=$this->getObject('user','security');
        return $u->isLoggedIn()&&($u->isAdmin()||($this->canCreate()&&(string)$u->userId()===$event['owner_id']));
    }
    public function canCheckIn(array $event,array $occurrence)
    {
        if($this->canManage($event)) return true;
        $u=$this->getObject('user','security'); $d=json_decode($occurrence['details'],true);
        return $u->isLoggedIn()&&in_array((string)$u->userId(),$d['helpers']??[],true);
    }
    public function token($scope,$id,$revision=1)
    {
        $key=(string)$this->getObject('dbsysconfig','sysconfig')->getValue('EVENTS_SIGNING_KEY','events');
        if(!preg_match('/^[a-f0-9]{64}$/D',$key)) throw new RuntimeException('Event signing key missing; update through Module Catalogue');
        return $id.'.'.hash_hmac('sha256',$scope.':'.$id.':'.(int)$revision,hex2bin($key));
    }
    public function tokenId($scope,$token,$revision=1)
    {
        if(!is_string($token)||!preg_match('/^([a-f0-9]{32})\.[a-f0-9]{64}$/D',$token,$m)) return null;
        return hash_equals($this->token($scope,$m[1],$revision),$token)?$m[1]:null;
    }
    public function ticketCode(array $ticket)
    { return strtoupper(substr(explode('.',$this->token('scan',$ticket['id'],$ticket['revision']))[1],0,20)); }
}
