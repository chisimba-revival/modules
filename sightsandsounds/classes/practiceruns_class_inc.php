<?php
/** Durable practice runs; checking never reselects a paper. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class practiceruns extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') { parent::init('tbl_ss_runs',$pearDb,$errorCallback); }
    public function latest(string $scope,string $uid,string $activity): ?array
    {
        return $this->selectPrepared('SELECT * FROM tbl_ss_runs WHERE contextcode = ? AND userid = ? AND activityid = ? ORDER BY createdat DESC, id DESC LIMIT 1',[$scope,$uid,$activity])[0]??null;
    }
    public function owned(string $scope,string $uid,string $id): array
    {
        $row=$this->selectPrepared('SELECT * FROM tbl_ss_runs WHERE contextcode = ? AND userid = ? AND id = ?',[$scope,$uid,$id])[0]??null;
        if (!$row) throw new DomainException('missing');
        return $row;
    }
    public function start(string $scope,string $uid,array $activity,bool $fresh=false): string
    {
        return $this->withDatabaseLock('ss-new-'.substr(hash('sha256',json_encode([$scope,$uid,$activity['id']])),0,16),function() use($scope,$uid,$activity,$fresh) {
            $old=$this->latest($scope,$uid,$activity['id']);
            if ($old && !$fresh && !$this->getObject('practicecompletions','sightsandsounds')->forRun($old['id'])) return $old['id'];
            $ids=practicerules::select(json_decode($activity['itemids'],true,512,JSON_THROW_ON_ERROR),(int)$activity['questioncount']);
            $state=[];
            foreach($ids as $id) $state[$id]=['answer'=>'','checks'=>0,'firstCorrect'=>null,'correct'=>false];
            $id=$this->insert(['contextcode'=>$scope,'userid'=>$uid,'activityid'=>$activity['id'],'state'=>json_encode($state,JSON_THROW_ON_ERROR),'revision'=>0,'createdat'=>(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u')]);
            if (!$id) throw new RuntimeException('Run save failed');
            return $id;
        });
    }
    public function checkAnswer(string $scope,string $uid,string $id,string $itemId,string $answer,int $revision): void
    {
        $this->withDatabaseLock('ss-run-'.substr(hash('sha256',$id),0,16),function() use($scope,$uid,$id,$itemId,$answer,$revision) {
            $this->transactionWithSavepoint(function() use($scope,$uid,$id,$itemId,$answer,$revision) {
                $row=$this->owned($scope,$uid,$id);
                if ($this->getObject('practicecompletions','sightsandsounds')->forRun($id) || (int)$row['revision']!==$revision) throw new DomainException('stale');
                $state=json_decode($row['state'],true,512,JSON_THROW_ON_ERROR);
                if (!isset($state[$itemId]) || mb_strlen($answer)>160 || trim($answer)==='') throw new DomainException('invalid');
                $item=$this->getObject('practicebank','sightsandsounds')->item($scope,$itemId);
                if (!$item) throw new DomainException('missing');
                $correct=practicerules::matches($answer,json_decode($item['aliases'],true,512,JSON_THROW_ON_ERROR));
                $entry=&$state[$itemId];
                if ($entry['checks']===0) $entry['firstCorrect']=$correct;
                $entry['answer']=$answer; ++$entry['checks']; $entry['correct']=$correct;
                if ($this->update('id',$id,['state'=>json_encode($state,JSON_THROW_ON_ERROR),'revision'=>$revision+1])===false) throw new RuntimeException('Answer save failed');
            });
        });
    }
}
