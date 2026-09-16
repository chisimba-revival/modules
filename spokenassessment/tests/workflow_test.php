<?php
/** Behavioural tests: no network, recordings or real student data. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public function getObject($name,$module=null) { return $GLOBALS['services'][$name]; } }
foreach (['spokenpolicy','spokenservice','spokenfeedback','spokenworker'] as $class) require dirname(__DIR__).'/classes/'.$class.'_class_inc.php';
$checks=0;
function check($condition,$label) { global $checks; ++$checks; if (!$condition) throw new RuntimeException($label); }
function denied(callable $fn,$label) { try {$fn();} catch (DomainException $e) {check(true,$label);return;} check(false,$label); }
class MemoryStore {
 public $activities=[],$attempts=[],$claim=null;
 public function now(){return '2026-09-16 12:00:00';}
 public function transaction($fn){$before=[$this->activities,$this->attempts];try{return $fn();}catch(Throwable $e){[$this->activities,$this->attempts]=$before;throw $e;}}
 public function activity($id,$lock=false){return $this->activities[$id]??null;}
 public function attempt($id,$lock=false){return $this->attempts[$id]??null;}
 public function putActivity($id,$values){$id=$id?:$values['id'];$this->activities[$id]=array_merge($this->activities[$id]??[],$values);}
 public function attempts($id,$owner=null){return array_values(array_filter($this->attempts,fn($a)=>$a['activity_id']===$id && ($owner===null || $a['userid']===$owner)));}
 public function addAttempt($values){$this->attempts[$values['id']]=$values;}
 public function changeAttempt($id,$values){$this->attempts[$id]=array_merge($this->attempts[$id],$values,['date_updated'=>$this->now()]);}
 public function claim(){return $this->claim;}
 public function finish($row,$values){$this->changeAttempt($row['id'],$values);return true;}
}
$store=new MemoryStore();$active=['student'=>true,'other'=>true,'teacher'=>true,'admin'=>true,'outsider'=>true];
$GLOBALS['services']=[
 'spokenstore'=>$store,
 'user'=>new class {public function isActive($id){return !empty($GLOBALS['active'][$id]);}public function lookupAdmin($id){return $id==='admin';}public function isContextLecturer($id,$context){return $id==='teacher' && $context==='course';}},
 'userservice'=>new class {public function findByUserId($id){return isset($GLOBALS['active'][$id])?['isactive'=>$GLOBALS['active'][$id]]:null;}},
 'dbcontext'=>new class {public function getContextDetails($c){return in_array($c,['course','elsewhere'],true)?['title'=>$c]:false;}},
 'groupservice'=>new class {public function groupIdForName($name){return $name;}public function isGroupMember($user,$group){return $group==='course^Students' && in_array($user,['student','other'],true) || $group==='course^Lecturers' && $user==='teacher';}},
 'dbsysconfig'=>new class {public $enabled=true;public function getValue($key,$module){return $this->enabled?'enabled':'disabled';}},
 'transcriptionservice'=>new class {public function isAvailable(){return true;}},
 'spokenfiles'=>new class {public $removed=[];public function upload($id,$file){return ['duration'=>10];}public function remove($id){$this->removed[]=$id;}public function path($id){return '/private/'.$id;}},
 'rubricservice'=>new class {public function getRubric($id){return ['id'=>$id,'contextCode'=>$id==='foreign'?'elsewhere':'course'];}public function getStructuredRubric($id){return $this->getRubric($id)+['criteria'=>[['label'=>'Evidence']]];}},
 'aiservice'=>new class {public $calls=0;public $request;public function transcribe($consumer,$path){++$this->calls;return ['ok'=>true,'text'=>'Original words','model'=>'fixture'];}public function execute($request){++$this->calls;$this->request=$request;return ['ok'=>true,'model'=>'fixture','data'=>['summary'=>'Thoughtful reasoning.','strengths'=>['Evidence'],'nextSteps'=>['Compare observations'],'criteria'=>[['criterion'=>'Evidence','feedback'=>'Make it specific.']]]];}}
];
$GLOBALS['active']=&$active;
$policy=new spokenpolicy();$GLOBALS['services']['spokenpolicy']=$policy;
$service=new spokenservice();$GLOBALS['services']['spokenservice']=$service;$service->init();
$feedback=new spokenfeedback();$GLOBALS['services']['spokenfeedback']=$feedback;
$attempt=['contextcode'=>'course','userid'=>'student'];
foreach(['student'=>true,'teacher'=>true,'admin'=>true,'other'=>false,'outsider'=>false,''=>false] as $who=>$expected) check($policy->mayRead($attempt,$who,'course')===$expected,'private access '.$who);
check(!$policy->mayRead($attempt,'admin','elsewhere'),'cross-course admin denied');
check(!$policy->mayChangeTranscript($attempt,'teacher','course'),'teacher cannot rewrite student transcript');
$input=['title'=>'Field explanation','prompt'=>'Explain evidence.','outcomes'=>'Reason carefully.','published'=>'1','rubric_id'=>'local'];
$id=$service->saveActivity('','teacher','course',$input);
denied(fn()=>$service->saveActivity('','student','course',$input),'student cannot create');
denied(fn()=>$service->saveActivity('','teacher','course',array_merge($input,['rubric_id'=>'foreign'])),'cross-course rubric denied');
denied(fn()=>$service->upload($id,'student','course',[],'0'),'consent required');
$a=$service->upload($id,'student','course',[],'1');
check($store->attempts[$a]['state']==='queued_transcription','transcription queued');
check($service->upload($id,'student','course',[],'1',$a)===$a && count($store->attempts)===1,'upload retry is idempotent');
denied(fn()=>$service->upload($id,'other','course',[],'1',$a),'another owner cannot reuse upload token');
$worker=new spokenworker();$store->claim=array_merge($store->attempts[$a],['state'=>'transcribing']);$worker->runOne();
check($store->attempts[$a]['original_transcript']==='Original words','original transcript retained');
denied(fn()=>$service->approve($a,'other','course','Replacement'),'another student cannot approve');
$service->approve($a,'student','course','Corrected original words 🌿');
check($store->attempts[$a]['original_transcript']==='Original words','correction does not overwrite original');
check($store->attempts[$a]['approved_transcript']==='Corrected original words 🌿','correction saved');
denied(fn()=>$service->approve($a,'student','course','Rewrite'),'approved text frozen');
$store->claim=array_merge($store->attempts[$a],['state'=>'feedback_processing']);$worker->runOne();
check($store->attempts[$a]['state']==='feedback_ready','feedback completes');
$api=$GLOBALS['services']['aiservice'];check(!isset($api->request['tools']),'no model tools');
check(str_contains($api->request['instructions'],'untrusted'),'instruction boundary');
denied(fn()=>$service->feedback($a,'student','course','My own mark'),'student cannot act as teacher');
$service->feedback($a,'teacher','course','Human feedback');check($store->attempts[$a]['teacher_feedback']==='Human feedback','human feedback separate');
$service->reflect($a,'student','course','I will compare evidence.');check($store->attempts[$a]['reflection']!=='','reflection saved');
$service->saveActivity($id,'teacher','course',array_merge($input,['version'=>'1','title'=>'Changed activity']));
check(json_decode($store->attempts[$a]['snapshot_json'],true)['title']==='Field explanation','immutable snapshot');
denied(fn()=>$service->saveActivity($id,'teacher','course',array_merge($input,['version'=>'1'])),'stale editor denied');
for($i=0;$i<4;$i++)$service->upload($id,'student','course',[],'1');
denied(fn()=>$service->upload($id,'student','course',[],'1'),'five-attempt cap');
check(count($GLOBALS['services']['spokenfiles']->removed)===1,'failed upload metadata removes orphan');
$active['student']=false;check(!$policy->mayRead($attempt,'student','course'),'inactive user denied');
$calls=$api->calls;$store->claim=array_merge($store->attempts[$a],['state'=>'feedback_processing']);$worker->runOne();check($api->calls===$calls,'revoked access prevents provider call');
$active['student']=true;$GLOBALS['services']['dbsysconfig']->enabled=false;$worker->runOne();check($api->calls===$calls,'disabled module prevents provider call');
$store->attempts[$a]['state']='transcribing';$store->attempts[$a]['date_updated']=gmdate('Y-m-d H:i:s');
denied(fn()=>$service->retry($a,'student','course'),'running request cannot be retried early');
$store->attempts[$a]['date_updated']='2000-01-01 00:00:00';$service->retry($a,'student','course');check($store->attempts[$a]['state']==='queued_transcription','explicit stale recovery');
check(!spokenfeedback::valid(['summary'=>'x','strengths'=>str_repeat('x',100),'nextSteps'=>[],'criteria'=>[]]),'malformed feedback rejected');
check(!spokenfeedback::valid(['summary'=>str_repeat('x',3001),'strengths'=>[],'nextSteps'=>[],'criteria'=>[]]),'unbounded feedback rejected');
echo "PASS $checks workflow, privacy, recovery and feedback checks.\n";
