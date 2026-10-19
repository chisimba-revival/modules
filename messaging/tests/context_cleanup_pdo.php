<?php
/** Local disposable records, always rolled back. No login/session or permission grants. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_CONTEXT_CLEANUP_TEST')!=='1')exit("Local opt-in required.\n");
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
$store=$engine->getObject('conversationstore','messaging');$ann=$engine->getObject('dbannouncements','announcements');
function check($ok,$label){if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
$code='deltest'.bin2hex(random_bytes(6));$other=$code.'other';$id=bin2hex(random_bytes(16));$shared=bin2hex(random_bytes(16));$only=bin2hex(random_bytes(16));
try{$store->transaction(function()use($store,$ann,$code,$other,$id,$shared,$only){
 $store->executePrepared('INSERT INTO tbl_messaging_channels_v2 (id,context_code,last_sequence) VALUES (?,?,0)',[$id,$code]);
 foreach(['posts','replies','reads','deliveries','bans'] as $t){
  $fields=['id'=>$id,'channel_id'=>$id];
  if($t==='posts')$fields+=['sender_id'=>'fixture','submission_id'=>$id,'body'=>'temporary','sequence_no'=>1,'created_at'=>gmdate('Y-m-d H:i:s')];
  if($t==='replies')$fields+=['reply_to'=>$id];
  if(in_array($t,['reads','deliveries','bans']))$fields+=['user_id'=>'fixture'];
  if(in_array($t,['reads','deliveries']))$fields+=['sequence_no'=>1];
  $store->executePrepared('INSERT INTO tbl_messaging_'.$t.'_v2 ('.implode(',',array_keys($fields)).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',array_values($fields));
 }
 foreach([$shared,$only] as $aid){
  $store->executePrepared("INSERT INTO tbl_announcements (id,title,message,createdon,createdby,contextid) VALUES (?,?,?,?,0,'context')",[$aid,'temporary','temporary',gmdate('Y-m-d H:i:s')]);
  $store->executePrepared('INSERT INTO tbl_announcements_context (id,announcementid,contextid) VALUES (?,?,?)',[bin2hex(random_bytes(16)),$aid,$code]);
  $store->executePrepared('INSERT INTO tbl_announcements_chat_receipts (id,announcement_id,sender_id,submission_id,body_hash) VALUES (?,?,?,?,?)',[hash('sha256',$aid),$aid,'fixture',$aid,hash('sha256','temporary')]);
 }
 $store->executePrepared('INSERT INTO tbl_announcements_context (id,announcementid,contextid) VALUES (?,?,?)',[bin2hex(random_bytes(16)),$shared,$other]);
 try{$store->transactionWithSavepoint(function()use($store,$ann,$code){$store->deleteForContext($code);$ann->deleteForContext($code);throw new LogicException('rollback-inner');});}catch(LogicException $e){if($e->getMessage()!=='rollback-inner')throw $e;}
 check((bool)$store->channel($id),'savepoint restores conversation after failure');
 check(count($store->selectPrepared('SELECT id FROM tbl_announcements_context WHERE contextid=?',[$code]))===2,'savepoint restores announcements after failure');
 $store->deleteForContext($code);$ann->deleteForContext($code);
 foreach($store->contextDeletionTables() as $table){$column=str_ends_with($table,'channels_v2')?'id':'channel_id';check(!$store->selectPrepared('SELECT id FROM '.$table.' WHERE '.$column.'=?',[$id]),$table.' cleaned');}
 check((bool)$store->selectPrepared('SELECT id FROM tbl_announcements WHERE id=?',[$shared]),'shared announcement preserved');
 check(!$store->selectPrepared('SELECT id FROM tbl_announcements WHERE id=?',[$only]),'course-only announcement removed');
 check(!$store->selectPrepared('SELECT id FROM tbl_announcements_chat_receipts WHERE announcement_id=?',[$only]),'course-only receipt removed');
 check((bool)$store->selectPrepared('SELECT id FROM tbl_announcements_context WHERE contextid=?',[$other]),'other course association preserved');
 throw new LogicException('rollback-fixtures');
});}catch(LogicException $e){if($e->getMessage()!=='rollback-fixtures')throw $e;}
check(!$store->channel($id),'outer fixture rollback');
try{$engine->getObject('contextdeletion','context')->remove($code,true);throw new LogicException('anonymous repair');}catch(DomainException $e){check($e->getMessage()==='forbidden','anonymous repair denied');}

try{$store->create($code);throw new LogicException('missing course recreated');}catch(DomainException $e){check($e->getMessage()==='unavailable','channel creation cannot resurrect a deleted course');}
