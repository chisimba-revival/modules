<?php
/** Formative workflow; never publishes marks or changes enrolment. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class spokenservice extends ChisimbaObject
{
    private $store;
    private $policy;
    public function init() { $this->store=$this->getObject('spokenstore'); $this->policy=$this->getObject('spokenpolicy'); }
    public function automaticEnabled() { return $this->getObject('dbsysconfig','sysconfig')->getValue('SPOKEN_AI_STATE','spokenassessment')==='enabled'; }
    public static function bounded($value, $limit, $required = false)
    {
        if (!is_string($value) || !mb_check_encoding($value,'UTF-8') || mb_strlen($value)>$limit || ($required && trim($value)==='')) throw new DomainException('invalid');
        return trim($value);
    }
    public function activity($id, $user, $context)
    {
        $row=$this->store->activity($id);
        if (!$row || $row['contextcode']!==$context || !$this->policy->member($user,$context)
            || (!$row['published'] && !$this->policy->teacher($user,$context))) throw new DomainException('unavailable');
        return $row;
    }
    public function attempt($id, $user, $context)
    {
        $row=$this->store->attempt($id);
        if (!$row || !$this->policy->mayRead($row,$user,$context)) throw new DomainException('unavailable');
        return $row;
    }
    public function saveActivity($id, $user, $context, array $input)
    {
        if (!$this->policy->teacher($user,$context)) throw new DomainException('unavailable');
        $title=self::bounded($input['title']??'',200,true); $prompt=self::bounded($input['prompt']??'',10000,true);
        $outcomes=self::bounded($input['outcomes']??'',10000,true); $rubric=self::bounded($input['rubric_id']??'',32);
        if ($rubric!=='') {
            $definition=$this->getObject('rubricservice','rubric')->getRubric($rubric);
            if (!$definition || $definition['contextCode']!==$context) throw new DomainException('invalid');
        }
        return $this->store->transaction(function () use ($id,$user,$context,$input,$title,$prompt,$outcomes,$rubric) {
            $row=$id!==''?$this->store->activity($id,true):null;
            if ($id!=='' && (!$row || $row['contextcode']!==$context)) throw new DomainException('unavailable');
            if ($row && (int)($input['version']??0)!==(int)$row['version']) throw new DomainException('changed');
            $values=['title'=>$title,'prompt'=>$prompt,'outcomes'=>$outcomes,'rubric_id'=>$rubric,'published'=>($input['published']??'')==='1'?1:0,'version'=>($row?(int)$row['version']:0)+1,'date_updated'=>$this->store->now()];
            if (!$row) $values=array_merge($values,['id'=>bin2hex(random_bytes(16)),'contextcode'=>$context,'userid'=>$user,'date_created'=>$this->store->now()]);
            $this->store->putActivity($id,$values); return $row?$id:$values['id'];
        });
    }
    public function upload($activityId, $user, $context, array $file, $consent, $uploadToken = null)
    {
        if ($consent!=='1') throw new DomainException('consent_required');
        $this->activity($activityId,$user,$context);
        $id=$uploadToken===null?bin2hex(random_bytes(16)):$uploadToken;
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D',$id)) throw new DomainException('invalid');
        $existing=$this->store->attempt($id);
        if ($existing) {
            if ($existing['userid']!==$user || $existing['contextcode']!==$context || $existing['activity_id']!==$activityId) throw new DomainException('unavailable');
            return $id; // A lost HTTP response must not consume another attempt.
        }
        $files=$this->getObject('spokenfiles');
        $inspection=$files->upload($id,$file);
        try {
            return $this->store->transaction(function () use ($activityId,$user,$context,$id,$inspection) {
                // Activity row serialises attempt limits across concurrent uploads.
                $activity=$this->store->activity($activityId,true);
                if (!$activity || $activity['contextcode']!==$context || !$activity['published'] || !$this->policy->member($user,$context)) throw new DomainException('unavailable');
                if (count($this->store->attempts($activityId,$user))>=5) throw new DomainException('attempt_limit');
                $rubric=null;
                if ($activity['rubric_id']!=='') {
                    $rubric=$this->getObject('rubricservice','rubric')->getStructuredRubric($activity['rubric_id']);
                    if (!$rubric || $rubric['contextCode']!==$context) throw new DomainException('invalid');
                }
                $snapshot=['title'=>$activity['title'],'prompt'=>$activity['prompt'],'outcomes'=>$activity['outcomes'],'rubric'=>$rubric,'activityVersion'=>(int)$activity['version'],'consentVersion'=>1];
                $enabled=$this->automaticEnabled() && $this->getObject('transcriptionservice','ai')->isAvailable();
                $this->store->addAttempt(['id'=>$id,'activity_id'=>$activityId,'contextcode'=>$context,'userid'=>$user,
                    'state'=>$enabled?'queued_transcription':'transcription_failed','claim_token'=>'','snapshot_json'=>json_encode($snapshot,JSON_THROW_ON_ERROR),
                    'original_transcript'=>'','approved_transcript'=>'','feedback_json'=>'{}','teacher_feedback'=>'','teacher_id'=>'','reflection'=>'',
                    'error_code'=>$enabled?'':'transcription_unavailable','transcription_model'=>'','feedback_model'=>'','duration_seconds'=>(int)ceil($inspection['duration']),
                    'date_created'=>$this->store->now(),'date_updated'=>$this->store->now()]); return $id;
            });
        } catch (Throwable $e) { $files->remove($id); throw $e; }
    }
    public function approve($id, $user, $context, $transcript)
    {
        $text=self::bounded($transcript,30000,true);
        return $this->store->transaction(function () use ($id,$user,$context,$text) {
            $row=$this->store->attempt($id,true);
            if (!$row || !$this->policy->mayChangeTranscript($row,$user,$context)) throw new DomainException('unavailable');
            if (!in_array($row['state'],['transcript_ready','transcription_failed'],true)) throw new DomainException('changed');
            $enabled=$this->automaticEnabled();
            $this->store->changeAttempt($id,['approved_transcript'=>$text,'state'=>$enabled?'queued_feedback':'feedback_failed','error_code'=>$enabled?'':'feedback_unavailable']);
        });
    }
    public function feedback($id, $user, $context, $text)
    {
        $text=self::bounded($text,15000,true);
        $this->store->transaction(function () use ($id,$user,$context,$text) {
            $row=$this->store->attempt($id,true);
            if (!$row || !$this->policy->mayRead($row,$user,$context) || !$this->policy->teacher($user,$context)) throw new DomainException('unavailable');
            $this->store->changeAttempt($id,['teacher_feedback'=>$text,'teacher_id'=>$user]);
        });
    }
    public function reflect($id, $user, $context, $text)
    {
        $text=self::bounded($text,10000);
        $this->store->transaction(function () use ($id,$user,$context,$text) {
            $row=$this->store->attempt($id,true);
            if (!$row || !$this->policy->mayChangeTranscript($row,$user,$context)) throw new DomainException('unavailable');
            $this->store->changeAttempt($id,['reflection'=>$text]);
        });
    }
    public function retry($id, $user, $context)
    {
        $this->store->transaction(function () use ($id,$user,$context) {
            $row=$this->store->attempt($id,true);
            if (!$row || !$this->policy->mayRead($row,$user,$context)) throw new DomainException('unavailable');
            $state=$row['state'];
            $stale=in_array($state,['transcribing','feedback_processing'],true)
                && strtotime($row['date_updated'].' UTC')<time()-900;
            if (!$stale && !in_array($state,['transcription_failed','feedback_failed'],true)) throw new DomainException('changed');
            $next=in_array($state,['transcribing','transcription_failed'],true)?'queued_transcription':'queued_feedback';
            $this->store->changeAttempt($id,['state'=>$next,'claim_token'=>'','error_code'=>'']);
        });
    }
}
