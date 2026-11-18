<?php
/** Quiz input queue: snapshot, bounded worker steps and atomic inactive results. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class quizinputjobs extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_mcq_quiz_input_jobs',$pearDb,$errorCallback); }

    public function enqueue($code,$testId,$source,$settings=null)
    {
        $user=$this->getObject('user','security');$uid=$user->userId();
        if(!$user->isLoggedIn()||!$this->getObject('contextauthorservice','contextadmin')->canManageContext($code,$uid))throw new DomainException('forbidden');
        $planner=$this->getObject('quizinputplan','mcqtests');
        $settings=$planner->settings($settings??[]);
        $plan=$planner->build($source,$settings['bankCount']);$plan['bankSettings']=$settings;
        return $this->transactionWithSavepoint(function()use($code,$testId,$uid,$plan){
            $test=$this->target($testId,true);
            if(!$test||$test['context']!==$code||$test['status']!=='inactive'||$this->hasQuestions($testId))throw new DomainException('quiz_changed');
            $existing=$this->selectPrepared('SELECT id FROM tbl_mcq_quiz_input_jobs WHERE testid=?',[$testId]);
            if($existing) return $existing[0]['id'];
            $id=bin2hex(random_bytes(16));$now=$this->storageNow();
            if(!$this->insert(['id'=>$id,'contextcode'=>$code,'userid'=>$uid,'testid'=>$testId,'status'=>'queued','input_json'=>json_encode($plan,JSON_THROW_ON_ERROR),'result_json'=>'[]','fingerprint'=>$this->fingerprint($test),'progress_completed'=>0,'progress_total'=>count($plan['requests']),'date_created'=>$now,'date_updated'=>$now]))throw new RuntimeException('Quiz queue insert failed');
            return $id;
        });
    }

    /** Private author status: source and generated answer material are not returned. */
    public function forContext($code)
    {
        if(!$this->getObject('contextauthorservice','contextadmin')->canManageContext($code,$this->getObject('user','security')->userId()))throw new DomainException('forbidden');
        return $this->selectPrepared('SELECT j.id,j.testid,j.status,j.progress_completed,j.progress_total,j.errorcode,t.name,b.bankid,b.bank_count,b.draw_count,b.selection FROM tbl_mcq_quiz_input_jobs j LEFT JOIN tbl_tests t ON t.id=j.testid LEFT JOIN tbl_mcq_quiz_banks b ON b.id=j.testid WHERE j.contextcode=? ORDER BY j.date_created,j.id',[$code]);
    }

    /** Explicit retries may incur another provider charge; never retry a live request. */
    public function retry($id,$code)
    {
        $this->forContext($code);
        return $this->transactionWithSavepoint(function()use($id,$code){
            $rows=$this->selectPrepared('SELECT * FROM tbl_mcq_quiz_input_jobs WHERE id=? AND contextcode=? FOR UPDATE',[$id,$code]);$job=$rows[0]??null;
            if(!$job||!in_array($job['status'],['failed','uncertain'],true))return false;
            if(!$this->usable($job,true))return false;
            return $this->update('id',$id,['status'=>'queued','claimtoken'=>null,'errorcode'=>null,'date_updated'=>$this->storageNow()])!==false;
        });
    }

    /** One worker step makes at most one external request, outside all transactions. */
    public function runOne()
    {
        $stale=$this->getObject('timeanddateservice','timeanddate-service')->toStorage(new DateTimeImmutable('-15 minutes',new DateTimeZone('UTC')));
        $job=$this->transactionWithSavepoint(function()use($stale){
            // A lost worker may already have spent a provider request. Human retry only.
            $this->executePrepared("UPDATE tbl_mcq_quiz_input_jobs SET status='uncertain',claimtoken=NULL,errorcode='interrupted' WHERE status='running' AND date_updated<?",[$stale]);
            $rows=$this->selectPrepared("SELECT * FROM tbl_mcq_quiz_input_jobs WHERE status='queued' ORDER BY date_created,id LIMIT 1 FOR UPDATE",[]);
            if(!$rows)return null;$job=$rows[0];$job['claimtoken']=bin2hex(random_bytes(16));
            if(!$this->usable($job,true)){$this->update('id',$job['id'],['status'=>'cancelled','errorcode'=>'quiz_changed','date_updated'=>$this->storageNow()]);return ['cancelled'=>true];}
            if($this->update('id',$job['id'],['status'=>'running','claimtoken'=>$job['claimtoken'],'date_updated'=>$this->storageNow()])===false)throw new RuntimeException('Quiz claim failed');return $job;
        });
        if(!$job)return ['selected'=>0];if(isset($job['cancelled']))return ['selected'=>1,'completed'=>1];
        $plan=json_decode($job['input_json'],true,512,JSON_THROW_ON_ERROR);$results=json_decode($job['result_json'],true,512,JSON_THROW_ON_ERROR);
        $request=$plan['requests'][(int)$job['progress_completed']];
        try{$outcome=$this->getObject('mcqaigenerator','mcqtests')->generate($request['source'],(int)$request['count'],null,array_column($results,'stem'));}
        catch(Throwable $error){$outcome=['ok'=>false,'error'=>'interrupted'];}
        return $this->transactionWithSavepoint(function()use($job,$plan,$results,$outcome){
            $rows=$this->selectPrepared('SELECT * FROM tbl_mcq_quiz_input_jobs WHERE id=? FOR UPDATE',[$job['id']]);$current=$rows[0]??null;
            if(!$current||$current['status']!=='running'||$current['claimtoken']!==$job['claimtoken'])return ['selected'=>1,'completed'=>0];
            $changes=['date_updated'=>$this->storageNow(),'claimtoken'=>null];
            if(!$this->usable($current,true))$changes+=['status'=>'cancelled','errorcode'=>'quiz_changed'];
            elseif(empty($outcome['ok']))$changes+=['status'=>($outcome['error']??'')==='interrupted'?'uncertain':'failed','errorcode'=>in_array($outcome['error']??'',['ai_unavailable','grounding_validation_failed','source_too_short','invalid_count','interrupted'],true)?$outcome['error']:'provider_failed'];
            else {
                $combined=array_merge($results,$outcome['questions']);$stems=array_map(fn($q)=>mb_strtolower(trim(preg_replace('/\s+/u',' ',$q['stem']))),$combined);
                if(count($stems)!==count(array_unique($stems)))$changes+=['status'=>'failed','errorcode'=>'duplicate_questions'];
                else {
                    $done=(int)$job['progress_completed']+1;$complete=$done>=count($plan['requests']);
                    if($complete && isset($plan['bankSettings'])){$this->getObject('quizbankpool','mcqtests')->publish($job,$combined,$plan['bankSettings']);}
                    elseif($complete){$insert=$this->getObject('mcqaigenerator','mcqtests')->insertQuestions($job['testid'],$combined,$job['userid']);if(empty($insert['ok']))throw new RuntimeException('Quiz question insertion failed');}
                    $changes+=['status'=>$complete?'review':'queued','result_json'=>json_encode($combined,JSON_THROW_ON_ERROR),'progress_completed'=>$done,'errorcode'=>null];
                }
            }
            if($this->update('id',$job['id'],$changes)===false)throw new RuntimeException('Quiz progress update failed');
            return ['selected'=>1,'completed'=>$changes['status']==='queued'?0:1];
        });
    }

    private function usable(array $job,$lock=false)
    {
        $test=$this->target($job['testid'],$lock);if(!$test||$test['context']!==$job['contextcode']||$test['status']!=='inactive'||!hash_equals($job['fingerprint'],$this->fingerprint($test))||$this->hasQuestions($job['testid']))return false;
        $users=$this->getObject('userservice','security');$user=$users->findByUserId($job['userid']);
        if(!$user||empty($user['isactive']))return false;
        $chapter=$this->selectPrepared('SELECT id FROM tbl_contextcontent_chaptercontext WHERE chapterid=? AND contextcode=?',[$test['chapter'],$job['contextcode']]);
        return $chapter && $this->getObject('dbcontext','context')->getContext($job['contextcode']) && $this->getObject('contextauthorservice','contextadmin')->isAuthor($job['contextcode'],$job['userid']);
    }
    private function target($id,$lock=false){return $this->selectPrepared('SELECT * FROM tbl_tests WHERE id=?'.($lock?' FOR UPDATE':''),[$id])[0]??null;}
    private function hasQuestions($id){return (bool)$this->selectPrepared('SELECT id FROM tbl_test_questions WHERE testid=? FOR UPDATE',[$id]);}
    private function fingerprint(array $test){return hash('sha256',json_encode($test,JSON_THROW_ON_ERROR));}
    private function storageNow(){return $this->getObject('timeanddateservice','timeanddate-service')->nowStorage();}
}
