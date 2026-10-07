<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public $objects=[];public function getObject($n,$m=''){return $this->objects[$n];}public function loadClass($n,$m){require_once dirname(__DIR__,3).'/framework/app/core_modules/'.$m.'/classes/'.$n.'_class_inc.php';}}
require __DIR__.'/../classes/contactservice_class_inc.php';
class UserFixture {public $admin=true;function isLoggedIn(){return $this->admin;}function isAdmin(){return $this->admin;}function userId(){return 'fixture-admin';}}
class ConfigFixture {function getValue($k,$m,$d=''){return ['CONTACT_MODE'=>'live','CONTACT_RATE_KEY'=>str_repeat('t',32),'CONTACT_TO'=>'inbox@example.invalid','CONTACT_FROM'=>'sender@example.invalid'][$k]??$d;}}
class GuardFixture {function check($i){return '192.0.2.1';}}
class MailFixture {public $count=0;function send(...$args){++$this->count;return true;}}
class StoreFixture {
 public $rows=[],$review=[],$blocks=[];
 function transaction($f){return $f();}
 function find($id,$lock=false){return $this->rows[$id]??null;}
 function accept($r,$key,$bucket,$reason=''){if(isset($this->rows[$r['id']])){if($this->rows[$r['id']]['fingerprint']!==$r['fingerprint'])throw new DomainException('conflict');return $this->rows[$r['id']];}$this->rows[$r['id']]=$r;if($reason)$this->moderate($r['id'],'spam',$reason,'automatic');return $r;}
 function claim($id){$r=$this->rows[$id];if($r['status']!=='pending')return null;$this->state($id,'sending');return $r;}
 function state($id,$s){$this->rows[$id]['status']=$s;}
 function blocked($h){return isset($this->blocks[$h]);}function block($h){$this->blocks[$h]=true;}function unblock($h){unset($this->blocks[$h]);}
 function moderate($id,$folder,$reason,$actor){$this->review[$id]=compact('folder','reason','actor');}
 function review($id){return $this->review[$id]??['folder'=>'inbox','reason'=>''];}
 function recent($page,$folder){return array_values(array_filter(array_map(fn($r)=>$r+($this->review[$r['id']]??['folder'=>'inbox','reason'=>'']),$this->rows),fn($r)=>$r['folder']===$folder));}
}
function ok($v){if(!$v)throw new RuntimeException('Contact regression');}
$s=new contactservice;$store=new StoreFixture;$mail=new MailFixture;$user=new UserFixture;
$s->objects=['contactstore'=>$store,'contactguard'=>new GuardFixture,'user'=>$user,'dbsysconfig'=>new ConfigFixture,'plainmailservice'=>$mail];$s->init();
$in=['id'=>str_repeat('a',32),'name'=>'Reader','email'=>'reader@example.invalid','subject'=>'Books','message'=>'Please quote for ten books.','website'=>''];
ok($s->submit($in,'192.0.2.1')==='sent');$s->submit($in,'192.0.2.1');ok($mail->count===1);
$bad=$in;$bad['id']=str_repeat('b',32);$bad['name']='AbCdEfGhIjKlMnOp';$s->submit($bad,'192.0.2.1');ok($mail->count===1&&$store->review[$bad['id']]['folder']==='spam');
$s->moderate([$bad['id']],'restore');ok($store->review[$bad['id']]['folder']==='inbox'&&$mail->count===1);$s->reviewPage(1);ok($store->review[$bad['id']]['folder']==='inbox');
$s->moderate([$in['id']],'block');$next=$in;$next['id']=str_repeat('c',32);$s->submit($next,'192.0.2.1');ok(!isset($store->rows[$next['id']])&&$mail->count===1);
$s->moderate([$in['id']],'unblock');$s->submit($next,'192.0.2.1');ok($mail->count===2);
$s->moderate([$in['id']],'trash');ok($store->review[$in['id']]['folder']==='trash');$s->moderate([$in['id']],'restore');ok(count($store->rows)===3);
$user->admin=false;foreach(['recent','moderate','reviewPage'] as $method){try{if($method==='moderate')$s->$method([$in['id']],'trash');else $s->$method(1);throw new RuntimeException('Denied user allowed');}catch(DomainException $e){ok($e->getMessage()==='forbidden');}}
echo "PASS: private inbox, quarantine without email, reversible moderation, exact-sender blocks and duplicate delivery protection\n";
