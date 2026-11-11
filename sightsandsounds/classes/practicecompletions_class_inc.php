<?php
/** Durable, idempotent finalisation. Practice answers are deliberately available offline.
 * Scores are recomputed here; no client-supplied score is trusted. @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class practicecompletions extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') { parent::init('tbl_ss_completions',$pearDb,$errorCallback); }
    public function forRun(string $run): ?array { return $this->selectPrepared('SELECT * FROM tbl_ss_completions WHERE runid = ?',[$run])[0]??null; }
    public function latest(string $scope,string $activity,string $uid): ?array {
        return $this->selectPrepared('SELECT * FROM tbl_ss_completions WHERE contextcode = ? AND activityid = ? AND userid = ? ORDER BY createdat DESC, id DESC LIMIT 1',[$scope,$activity,$uid])[0]??null;
    }
    public function finish(string $scope,string $uid,string $id,int $revision,?array $answers=null): array
    {
        return $this->withDatabaseLock('ss-run-'.substr(hash('sha256',$id),0,16),function()use($scope,$uid,$id,$revision,$answers){
            return $this->transactionWithSavepoint(function()use($scope,$uid,$id,$revision,$answers){
                $runs=$this->getObject('practiceruns','sightsandsounds');
                $run=$runs->owned($scope,$uid,$id);
                $state=json_decode($run['state'],true,512,JSON_THROW_ON_ERROR);
                $submitted=$answers??array_map(fn($entry)=>['answer'=>$entry['answer'],'checks'=>$entry['checks']],$state);
                if (count($submitted)!==count($state) || array_diff_key($submitted,$state)) throw new DomainException('invalid');
                $normal=[];
                foreach($state as $qid=>$entry) {
                    $value=$submitted[$qid]??null;
                    if (!is_array($value) || !is_string($value['answer']??null) || !mb_check_encoding($value['answer'],'UTF-8') || mb_strlen($value['answer'])>160
                        || !is_int($value['checks']??null) || $value['checks']<0 || $value['checks']>1000000) throw new DomainException('invalid');
                    $normal[$qid]=['answer'=>$value['answer'],'checks'=>$value['checks']];
                }
                $digest=hash('sha256',json_encode([$revision,$normal],JSON_THROW_ON_ERROR));
                $old=$this->forRun($id);
                if ($old) {
                    if ($answers!==null && !hash_equals($old['digest'],$digest)) throw new DomainException('stale');
                    return $old;
                }
                if ((int)$run['revision']!==$revision) throw new DomainException('stale');
                $correct=0;
                foreach($normal as $qid=>$value) {
                    $item=$this->getObject('practicebank','sightsandsounds')->item($scope,$qid);
                    if (!$item) throw new DomainException('missing');
                    $matched=$value['checks']>0 && practicerules::matches($value['answer'],json_decode($item['aliases'],true,512,JSON_THROW_ON_ERROR));
                    $correct+=(int)$matched;
                    $state[$qid]=array_merge($state[$qid],$value,['correct'=>$matched]);
                }
                if ($runs->update('id',$id,['state'=>json_encode($state,JSON_THROW_ON_ERROR),'revision'=>$revision+1])===false) throw new RuntimeException('Run finalisation failed');
                $saved=$this->insert(['runid'=>$id,'contextcode'=>$scope,'activityid'=>$run['activityid'],'userid'=>$uid,'digest'=>$digest,
                    'correct'=>$correct,'total'=>count($state),'createdat'=>(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u')]);
                if (!$saved) throw new RuntimeException('Receipt save failed');
                return $this->forRun($id);
            });
        });
    }
}
