<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($n,$m=null){return $GLOBALS['objects'][$n];}function loadClass($n,$m=null){}}
class dbTable extends ChisimbaObject {}
require __DIR__.'/../../audience/classes/audienceservice_class_inc.php';
require __DIR__.'/../classes/webinarschedule_class_inc.php';
require __DIR__.'/../classes/webinarregistrationservice_class_inc.php';
class TestAudience extends audienceservice {
 public $row=['id'=>'c','state'=>'pending','revision'=>0,'verified_at'=>null];public $audit=[];
 function getRow($key,$email,$table=null){return $email==='test@example.org'?$this->row:null;}
 function contact($email,$name){return $this->row;}function lock($id){return $this->row;}function one($id){return $this->row;}function checkRate($a,$b,$now=null){}
 function update($key,$id,$data){$this->row=array_merge($this->row,$data);return true;}
 function insert($r,$table=null){$this->audit[]=$r;return true;}
}
class TestRegs {public $row=null;function forContact($a,$b){return $this->row;}function insert($r){$this->row=$r;return true;}function update($a,$b,$r){$this->row=$r;return true;}function beginTransaction(){}function commitTransaction(){}function rollbackTransaction(){}}
class TestService extends webinarregistrationservice {public $sent=[];function queue(array $r,array $w,array $c,$kind,$token=''){$this->sent[]=$kind;}}
function check($ok,$msg){if(!$ok)throw new RuntimeException($msg);}
$config=new class {public $value='TRUE';function getValue($a,$b){return $this->value;}};
$a=new TestAudience();$regs=new TestRegs();$GLOBALS['objects']=['audienceservice'=>$a,'webinarregistrations'=>$regs,'webinarstore'=>new stdClass(),'communicationservice'=>new stdClass(),'dbsysconfig'=>$config,'webinarrenderer'=>new class{function text($k){return $k;}}];$s=new TestService();$s->init();
$w=['id'=>'w','kind'=>'webinar','status'=>'published','presented_at'=>'2030-01-01 19:00:00','payload'=>json_encode(['timezone'=>'Africa/Johannesburg','registration_open'=>true])];
check($s->register($w,'Test','test@example.org',true,'test')==='pending','Verified mode result');check($regs->row['state']==='pending'&&$a->row['state']==='pending','No premature consent');check($s->sent===['verify'],'Only verification email');
$s->register($w,'Test','test@example.org',true,'test');check(count($s->sent)===1,'Pending duplicate no mail');
$config->value='FALSE';check($s->register($w,'Test','test@example.org',true,'test')==='confirmed','Retry pending in immediate mode');check($regs->row['state']==='confirmed'&&$a->row['state']==='subscribed','Immediate completion');check($a->row['verified_at']===null,'No false mailbox verification');check($s->sent===['verify','confirmed'],'Details email replaces verification');
$s->register($w,'Test','test@example.org',true,'test');check(count($s->sent)===2,'Immediate duplicate no mail');
$a->row['state']='unsubscribed';$a->row['revision']=1;
try{$s->register($w,'Test','test@example.org',false,'test');throw new RuntimeException('Missing consent accepted');}catch(DomainException $ex){check($ex->getMessage()==='invalid','Explicit consent required');}
check($a->row['state']==='unsubscribed','Unsubscribe preserved without consent');
$s->register($w,'Test','test@example.org',true,'test');check($a->row['state']==='subscribed','Explicit resubscription');check(end($a->audit)['action']==='subscribe_unverified','Consent method recorded');
foreach([null,'','invalid','TRUE'] as $value){$config->value=$value;check($s->requiresEmailVerification(),'Safe default');}
echo "PASS verified/immediate modes, retry, duplicate submission, explicit resubscription and accurate consent audit.\n";

$config->value='FALSE';
check($s->alreadyRegistered($w,'TEST@example.org'),'Normalised confirmed duplicate lookup');
check(!$s->alreadyRegistered($w,'other@example.org'),'Unknown email not registered');
$regs->row['state']='pending';check(!$s->alreadyRegistered($w,'test@example.org'),'Pending booking not called confirmed');
$regs->row['state']='confirmed';$a->row['revision']++;check(!$s->alreadyRegistered($w,'test@example.org'),'Revoked revision not called registered');
echo "PASS duplicate lookup normalisation, missing email, pending and revoked states.\n";
