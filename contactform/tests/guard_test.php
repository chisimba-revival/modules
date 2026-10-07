<?php
require __DIR__.'/service_test.php';
require __DIR__.'/../classes/contactguard_class_inc.php';
require dirname(__DIR__,3).'/framework/app/core_modules/abuseprotection/classes/abuseprotectionservice.php';
class GuardEvents implements AbuseEventRepositoryInterface {
 public $events=[];
 function countFailures($a,$s,$t){return 0;}function record(array $e){$this->events[]=$e;return true;}function purgeExpired($t){return 0;}
 function reserveBudgets($lock,array $budgets){foreach($budgets as $b){$n=0;foreach($this->events as $e)if($e['action_key']===$b['action_key']&&$e['subject_hash']===$b['subject_hash']&&$e['occurred_at']>$b['since'])++$n;if($n>=$b['limit'])return false;}foreach($budgets as $b)$this->record($b);return true;}
}
class GuardComposition {public $abuse;function build(){return ['abuse'=>$this->abuse];}}
$now=100000;$repo=new GuardEvents;$composition=new GuardComposition;$composition->abuse=new AbuseProtectionService($repo,str_repeat('g',32),function()use(&$now){return $now;});
$guard=new contactguard;$guard->objects=['nativeauthwebcomposition'=>$composition,'dbsysconfig'=>new ConfigFixture];$_SERVER['REMOTE_ADDR']='192.0.2.90';$_SERVER['HTTP_X_FORWARDED_FOR']='198.51.100.9';
$e=$guard->evidence();$input=['email'=>'reader@example.invalid','website'=>''];foreach($e as $k=>$v)$input['abuse_'.$k]=$v;
try{$guard->check($input);throw new RuntimeException('Fast bot accepted');}catch(DomainException $e){ok($e->getMessage()==='protection');}
$now+=3;ok($guard->check($input)==='192.0.2.90');
$bad=$input;$bad['abuse_signature']=str_repeat('0',64);try{$guard->check($bad);throw new RuntimeException('Forgery accepted');}catch(DomainException $e){ok($e->getMessage()==='protection');}
$bad=$input;$bad['website']='spam';try{$guard->check($bad);throw new RuntimeException('Honeypot accepted');}catch(DomainException $e){ok($e->getMessage()==='protection');}
$guard->check($input);$guard->check($input);$_SERVER['REMOTE_ADDR']='192.0.2.91';try{$guard->check($input);throw new RuntimeException('Sender budget bypassed');}catch(DomainException $e){ok($e->getMessage()==='limited');}
echo "PASS: signed evidence, minimum age, honeypot, sender budget across IPs and untrusted proxy rejection\n";
