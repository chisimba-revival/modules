<?php
/** Explicit local-only fixture. Requires the disposable REST account; never use production. */
if(PHP_SAPI!=='cli'||getenv('MESSAGING_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
$f=json_decode(file_get_contents('/tmp/restapi-local-fixture.json'),true,512,JSON_THROW_ON_ERROR);$user=$e->getObject('userservice','security')->findByUserId($f['userId']);
if(!$user||$user['username']!==$f['username']||!str_starts_with($f['username'],'api_test_'))throw new RuntimeException('Not a disposable fixture');
$stack=$e->getObject('nativeauthwebcomposition','security')->build();if(!$stack['sessions']->establish($f['userId'],['username'=>$f['username'],'provider'=>'test']))throw new RuntimeException('Session failed');
$service=$e->getObject('conversationservice','messaging');$store=$e->getObject('conversationstore','messaging');$contexts=$e->getObject('dbcontext','context');$op=$argv[1]??'';
function check($ok,$name){if(!$ok)throw new RuntimeException($name);echo "PASS $name\n";}
if($op==='prepare') {
 $f['conversationContext']='msgtest'.bin2hex(random_bytes(5));file_put_contents('/tmp/restapi-local-fixture.json',json_encode($f));
 if(!$contexts->createContext($f['conversationContext'],'Temporary messaging test','Published','Private'))throw new RuntimeException('Context create failed');
 $f['conversationChannel']=$service->create($f['conversationContext']);file_put_contents('/tmp/restapi-local-fixture.json',json_encode($f));
 foreach(['channels','posts','reads','bans']as$s){$table='tbl_messaging_'.$s.'_v2';$row=$store->selectPrepared('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table]);check(strtolower($row[0]['engine'])==='innodb',$s.' transactional');}
 echo "Fixture prepared\n";
}elseif($op==='service') {
 $id=$f['conversationChannel'];$key=str_repeat('a',32);$a=$service->send($id,$key,'First <script>plain text</script>');$b=$service->send($id,$key,'First <script>plain text</script>');check($a['id']===$b['id'],'same submission returns same receipt');
 try{$service->send($id,$key,'Changed');throw new RuntimeException('Conflict accepted');}catch(DomainException $x){check($x->getMessage()==='conflict','changed retry conflicts');}
 for($i=0;$i<55;$i++)$service->send($id,bin2hex(random_bytes(16)),'History '.$i);
 $latest=$service->history($id);check(count($latest['messages'])===50&&$latest['older']!==null,'bounded latest page');$older=$service->history($id,$latest['older']);check(count($older['messages'])===6,'nonoverlapping earlier page');
 $service->markRead($id,40);$service->markRead($id,10);check($service->history($id)['unread']===16,'read position never regresses');
 try{$service->markRead($id,999);throw new RuntimeException('Future read accepted');}catch(DomainException $x){check($x->getMessage()==='invalid','future read rejected');}
 $store->executePrepared('INSERT INTO tbl_messaging_bans_v2 (id,channel_id,user_id) VALUES (?,?,?)',[bin2hex(random_bytes(16)),$id,$f['userId']]);
 foreach(['history','send']as$method){try{if($method==='history')$service->history($id);else $service->send($id,$key,'First <script>plain text</script>');throw new RuntimeException('Ban ignored');}catch(DomainException $x){check($x->getMessage()==='unavailable','ban blocks '.$method.' including receipt');}}
 $store->executePrepared('DELETE FROM tbl_messaging_bans_v2 WHERE channel_id=? AND user_id=?',[$id,$f['userId']]);
}elseif($op==='announcement-send') {
 $chat=$e->getObject('announcementchat','announcements');$key=str_repeat('8',32);
 foreach(['tbl_announcements','tbl_announcements_context','tbl_announcements_chat_receipts']as$table){$engine=$store->selectPrepared('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table]);check(strtolower($engine[0]['engine'])==='innodb','announcement transaction engine');}
 $a=$chat->send($f['conversationContext'],false,$key,'A message <script>literal</script>');
 $b=$chat->send($f['conversationContext'],false,$key,'A message <script>literal</script>');check($a['id']===$b['id'],'announcement retry uses same receipt');
 $f['announcementIds'][]=$a['id'];file_put_contents('/tmp/restapi-local-fixture.json',json_encode($f));
 try{$chat->send($f['conversationContext'],false,$key,'Changed');throw new RuntimeException('Conflict accepted');}catch(DomainException $x){check($x->getMessage()==='conflict','changed announcement retry rejected');}
 try{$chat->send('',true,$key,'Site');throw new RuntimeException('Site permission bypass');}catch(DomainException $x){check($x->getMessage()==='unavailable','course author cannot publish site announcements');}
 $before=count($store->selectPrepared('SELECT id FROM tbl_announcements_chat_receipts',[]));
 try{$store->transactionWithSavepoint(function()use($chat,$f){$chat->send($f['conversationContext'],false,str_repeat('6',32),'Rollback announcement');throw new RuntimeException('injected');});}catch(RuntimeException $x){if($x->getMessage()!=='injected')throw $x;}
 check(count($store->selectPrepared('SELECT id FROM tbl_announcements_chat_receipts',[]))===$before,'announcement send rolls back with parent transaction');
 $rows=$chat->messages($e->getObject('conversationview','messaging')->announcements($f['conversationContext']));
 $match=array_values(array_filter($rows,fn($r)=>$r['id']===$a['id']))[0];check($match['body']==='A message <script>literal</script>'&&$match['submission_id']===$key,'announcement text and own retry receipt preserved');
}elseif($op==='presentation') {
$v=$e->getObject('conversationview','messaging');
check(str_ends_with($v->timestamp('2026-10-18 08:34:00'),'['.$e->getObject('timeanddateservice','timeanddate-service')->siteTimezone().']'),'timestamp uses server timezone');
check($v->timestamp('invalid')==='','invalid timestamp not displayed');
$plugins=$e->getObject('dbcontextmodules','context');
try {
 $plugins->addModule($f['conversationContext'],'messaging');$plugins->addModule($f['conversationContext'],'messaging');
 check(count(array_filter($plugins->getContextModules($f['conversationContext']),fn($v)=>$v==='messaging'))===1,'duplicate plugins returned once');
}finally{$plugins->deleteModulesForContext($f['conversationContext']);}
}elseif($op==='announcements') {
 $ids=[];$ann=$e->getObject('dbannouncements','announcements');
 try {
  foreach(['site','context','other','admin','expired','future']as$type){
   $aid=bin2hex(random_bytes(16));$ids[$type]=$aid;
   $store->executePrepared('INSERT INTO tbl_announcements (id,title,message,createdon,contextid,audience,publish_at,expires_at) VALUES (?,?,?,?,?,?,?,?)',[$aid,'Messaging fixture','Synthetic text',gmdate('Y-m-d H:i:s'),in_array($type,['context','other'],true)?'context':'site',$type==='admin'?'admins':'everyone',$type==='future'?'2099-01-01 00:00:00':null,$type==='expired'?'2000-01-01 00:00:00':null]);
   if(in_array($type,['context','other'],true))$store->executePrepared('INSERT INTO tbl_announcements_context (id,announcementid,contextid) VALUES (?,?,?)',[bin2hex(random_bytes(16)),$aid,$type==='context'?$f['conversationContext']:'msgtest-other']);
  }
  foreach(['site','context']as$scope){$rows=$ann->getVisibleAnnouncements([$f['conversationContext']],['everyone'],100,0,$scope);$found=array_values(array_intersect(array_column($rows,'id'),$ids));check($found===[$ids[$scope]],'announcement '.$scope.' excludes other scopes, audiences and inactive dates');}
 }finally{foreach($ids as$aid){$store->executePrepared('DELETE FROM tbl_announcements_context WHERE announcementid=?',[$aid]);$store->executePrepared('DELETE FROM tbl_announcements WHERE id=?',[$aid]);}}
}elseif($op==='rollback') {
 $id=$f['conversationChannel'];$key=bin2hex(random_bytes(16));$before=$store->channel($id)['last_sequence'];
 try{$store->locked($id,function($channel)use($store,$id,$key,$f){$store->append($id,$f['userId'],$key,'Rollback fixture',(int)$channel['last_sequence']+1);throw new RuntimeException('injected rollback');});}catch(RuntimeException $x){if($x->getMessage()!=='injected rollback')throw $x;}
 check($store->receipt($id,$f['userId'],$key)===null,'rollback removes partial post');check($store->channel($id)['last_sequence']===$before,'rollback restores sequence');
}elseif($op==='race') {$row=$service->send($f['conversationChannel'],str_repeat('b',32),'Concurrent retry');echo $row['id'];
}elseif($op==='revoke'||$op==='restore') {
 $g=$e->getObject('groupservice','groupadmin');$model=$e->getObject('groupadminmodel','groupadmin');$gid=$model->getLeafId([$f['conversationContext'],'Lecturers']);$pid=$e->getObject('identityservice','security')->permissionUserIdForUser($f['userId']);
 if($op==='revoke')$g->removeMembership($gid,$pid);else $g->ensureMembership($gid,$pid);
 echo "Membership $op\n";
}elseif($op==='replies') {
 $id=$f['conversationChannel'];$source=$service->send($id,bin2hex(random_bytes(16)),'Original <script>quoted text</script>');$key=bin2hex(random_bytes(16));
 $reply=$service->send($id,$key,'A reply',$source['id']);$again=$service->send($id,$key,'A reply',$source['id']);check($reply['id']===$again['id'],'reply retry keeps same receipt');
 $history=$service->history($id);$last=end($history['messages']);check($last['reply']['id']===$source['id']&&$last['reply']['body']===$source['body'],'canonical quote returned');
 try{$service->send($id,$key,'A reply','');throw new RuntimeException('Changed reply accepted');}catch(DomainException $x){check($x->getMessage()==='conflict','changed reply retry rejected');}
 $other=bin2hex(random_bytes(16));$foreign=$store->append($other,$f['userId'],bin2hex(random_bytes(16)),'Foreign private text',1);
 try{try{$service->send($id,bin2hex(random_bytes(16)),'Forbidden quote',$foreign['id']);throw new RuntimeException('Cross-channel quote accepted');}catch(DomainException $x){check($x->getMessage()==='invalid','cross-channel quote rejected');}}finally{$store->executePrepared('DELETE FROM tbl_messaging_posts_v2 WHERE channel_id=?',[$other]);}
}elseif($op==='browserreply') {
 $channel=$service->forContext($f['conversationContext']);$service->markRead($channel['id'],(int)$channel['last_sequence']);$service->send($channel['id'],bin2hex(random_bytes(16)),'Incoming local browser check');echo "Browser reply added\n";
 }elseif($op==='receipts') {
 $id=$f['conversationChannel'];$actor=$f['userId'];$row=$service->send($id,bin2hex(random_bytes(16)),'Receipt test');$seq=(int)$row['sequence_no'];
  $countBefore=count($store->selectPrepared('SELECT id FROM tbl_messaging_reads_v2 WHERE channel_id=? AND user_id=?',[$id,$actor]));
 $service->markDelivered($id,$seq);$service->markRead($id,$seq);$service->markRead($id,$seq);
 $countAfter=count($store->selectPrepared('SELECT id FROM tbl_messaging_reads_v2 WHERE channel_id=? AND user_id=?',[$id,$actor]));check($countAfter===max(1,$countBefore),'repeat read does not add duplicate positions');
 check($store->readPosition($id,$actor)===$seq,'furthest read position retained');
 $history=$service->history($id);$last=end($history['messages']);check($last['receipt']==='sent','own reads do not mark own messages read');
 $store->markDelivered($id,'synthetic-other',$seq);$history=$service->history($id);$last=end($history['messages']);check($last['receipt']==='delivered','other delivery gives two grey ticks');
 $store->markRead($id,'synthetic-other',$seq);$history=$service->history($id);$last=end($history['messages']);check($last['receipt']==='read','other read gives blue ticks');
 try{$service->markDelivered($id,$seq+100);throw new RuntimeException('Future delivery accepted');}catch(DomainException $x){check($x->getMessage()==='invalid','future delivery rejected');}
 $store->markDelivered($id,'synthetic-other',0);$positions=$store->receiptPositions($id,$actor);check($positions['delivered']===$seq,'delivery cannot regress');
}elseif($op==='cleanup') {
 foreach(($f['announcementIds']??[])as$aid){$store->executePrepared('DELETE FROM tbl_announcements_chat_receipts WHERE announcement_id=?',[$aid]);$store->executePrepared('DELETE FROM tbl_announcements_context WHERE announcementid=?',[$aid]);$store->executePrepared('DELETE FROM tbl_announcements WHERE id=?',[$aid]);}
 if(!str_starts_with($f['conversationContext']??'','msgtest'))throw new RuntimeException('Missing fixture scope');
 foreach(['replies','posts','reads','deliveries','bans']as$s)$store->executePrepared('DELETE FROM tbl_messaging_'.$s.'_v2 WHERE channel_id=?',[$f['conversationChannel']]);
 $store->executePrepared('DELETE FROM tbl_messaging_channels_v2 WHERE id=?',[$f['conversationChannel']]);$contexts->deleteContext($f['conversationContext']);echo "Conversation fixture removed\n";
}else throw new RuntimeException('Unknown operation');
