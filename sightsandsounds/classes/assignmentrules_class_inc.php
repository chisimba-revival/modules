<?php
/** Immutable assignment rules and attempt accounting. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class assignmentrules extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') { parent::init('tbl_ss_assignment_rules',$pearDb,$errorCallback); }
    public function time() { return $this->getObject('timeanddateservice','timeanddate-service'); }
    public function forActivity(string $scope,string $id): array {
        $row=$this->selectPrepared('SELECT rules FROM tbl_ss_assignment_rules WHERE contextcode = ? AND activityid = ?',[$scope,$id])[0]??null;
        return $row?json_decode($row['rules'],true,512,JSON_THROW_ON_ERROR):['mode'=>'practice','retries'=>0,'opens'=>null,'due'=>null];
    }
    public function fromInput(array $input): array {
        $mode=$input['mode']??'practice';$retries=$input['retries']??'0';
        if(!in_array($mode,['practice','formative'],true)||!is_string($retries)||!ctype_digit($retries)||(int)$retries>20)throw new DomainException('invalid');
        $rules=['mode'=>$mode,'retries'=>$mode==='practice'?0:(int)$retries];
        foreach(['opens','due'] as $key){
            $value=$input[$key]??'';
            if(!is_string($value))throw new DomainException('invalid');
            $date=$value===''?null:$this->time()->parseLocal($value,null,'Y-m-d\TH:i');
            if($value!==''&&!$date)throw new DomainException('invalid');
            $rules[$key]=$date?$this->time()->toStorage($date):null;
        }
        if($rules['opens']&&$rules['due']&&$rules['opens']>=$rules['due'])throw new DomainException('invalid');
        return $rules;
    }
    public function savePublished(string $scope,string $id,array $rules): void {
        if(!$this->insert(['contextcode'=>$scope,'activityid'=>$id,'rules'=>json_encode($rules,JSON_THROW_ON_ERROR)]))throw new RuntimeException('Assignment rules save failed');
    }
    public function budget(string $scope,string $id,string $uid): array {
        $rules=$this->forActivity($scope,$id);
        $used=(int)$this->selectPrepared('SELECT COUNT(*) AS n FROM tbl_ss_runs WHERE contextcode = ? AND activityid = ? AND userid = ?',[$scope,$id,$uid])[0]['n'];
        $extra=(int)$this->selectPrepared('SELECT COUNT(*) AS n FROM tbl_ss_attempt_grants WHERE contextcode = ? AND activityid = ? AND userid = ?',[$scope,$id,$uid])[0]['n'];
        return ['used'=>$used,'remaining'=>$rules['mode']==='practice'?null:max(0,1+$rules['retries']+$extra-$used),'extra'=>$extra];
    }
    public function allowNew(string $scope,string $id,string $uid): void {
        $rules=$this->forActivity($scope,$id);$now=$this->time()->nowStorage();
        if($rules['opens']&&$now<$rules['opens'])throw new DomainException('notopen');
        if($rules['due']&&$now>$rules['due'])throw new DomainException('closed');
        if($this->budget($scope,$id,$uid)['remaining']===0)throw new DomainException('attemptlimit');
    }
    public function late(string $scope,string $id,string $received): bool {
        $due=$this->forActivity($scope,$id)['due'];return $due!==null&&substr($received,0,19)>$due;
    }
    public function grant(string $scope,string $id,string $uid,string $reason,string $request): void {
        $access=$this->getObject('practiceaccess','sightsandsounds');$access->requireCapability('grant_attempts',$scope);
        if(!$this->getObject('practicebank','sightsandsounds')->activity($scope,$id)||$this->forActivity($scope,$id)['mode']!=='formative')throw new DomainException('invalid');
        if(!preg_match('/^[a-f0-9]{32}$/D',$request)||trim($reason)===''||mb_strlen($reason)>1000||!mb_check_encoding($reason,'UTF-8'))throw new DomainException('invalid');
        if(!$this->getObject('usercontext','context')->isContextMember($uid,$scope))throw new DomainException('denied');
        $actor=(string)$this->getObject('user','security')->userId();
        $this->withDatabaseLock('ss-grant-'.substr(hash('sha256',$request),0,15),function()use($scope,$id,$uid,$reason,$request,$actor){
            $old=$this->selectPrepared('SELECT * FROM tbl_ss_attempt_grants WHERE requestid = ?',[$request])[0]??null;
            if($old){if($old['contextcode']!==$scope||$old['activityid']!==$id||$old['userid']!==$uid||$old['actor']!==$actor||$old['reason']!==trim($reason))throw new DomainException('stale');return;}
            if($this->executePrepared('INSERT INTO tbl_ss_attempt_grants (id,contextcode,activityid,userid,actor,reason,createdat,requestid) VALUES (?,?,?,?,?,?,?,?)',
                [bin2hex(random_bytes(16)),$scope,$id,$uid,$actor,trim($reason),$this->time()->nowStorage(),$request])===false)throw new RuntimeException('Grant save failed');
        });
    }
    public function results(string $scope,string $id): array {
        $this->getObject('practiceaccess','sightsandsounds')->requireCapability('view_results',$scope);
        return $this->selectPrepared('SELECT r.id,r.userid,r.createdat AS started,c.correct,c.total,c.createdat AS received FROM tbl_ss_runs r LEFT JOIN tbl_ss_completions c ON c.runid = r.id WHERE r.contextcode = ? AND r.activityid = ? ORDER BY r.createdat,r.id',[$scope,$id]);
    }
    public function grants(string $scope,string $id): array {
        $this->getObject('practiceaccess','sightsandsounds')->requireCapability('view_results',$scope);
        return $this->selectPrepared('SELECT * FROM tbl_ss_attempt_grants WHERE contextcode = ? AND activityid = ? ORDER BY createdat,id',[$scope,$id]);
    }
}
