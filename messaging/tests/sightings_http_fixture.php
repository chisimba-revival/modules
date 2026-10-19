<?php
/** Named local-only fixture; does not establish a session or grant administrator access. */
if(PHP_SAPI!=='cli'||getenv('SIGHTINGS_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='chisimba.test')throw new RuntimeException('Local only');
$path='/tmp/sightings-http-fixture.json';$op=$argv[1]??'';$users=$e->getObject('userservice','security');$store=$e->getObject('conversationstore','messaging');$groups=$e->getObject('groupservice','groupadmin');
if($op==='prepare'){
 if(is_file($path)){ $f=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$existing=$users->findByUserId($f['userId']);if(!$existing||$existing['username']!==$f['username']||!str_starts_with($f['username'],'sighting_test_'))throw new RuntimeException('Not a resumable fixture'); }else{
 $username='sighting_test_'.bin2hex(random_bytes(4));$password=bin2hex(random_bytes(24)).'!aA2';
 $f=$e->getObject('userprovisioningservice','security')->createLocalUser(['userId'=>$users->generateUserId(),'username'=>$username,'firstName'=>'Sighting','surname'=>'Fixture','emailAddress'=>$username.'@example.invalid','howCreated'=>'useradmin','isActive'=>true],$password);
 if(empty($f['ok']))throw new RuntimeException('Fixture account failed');
 $f+=['username'=>$username,'password'=>$password,'context'=>'sighttest'.bin2hex(random_bytes(5))];file_put_contents($path,json_encode($f));chmod($path,0600);
 }
 $contexts=$e->getObject('dbcontext','context');
 if(!$contexts->getContextDetails($f['context'])){
 if(!$contexts->insert(['contextcode'=>$f['context'],'title'=>'Synthetic sightings HTTP test','menutext'=>'Synthetic test','access'=>'Public','status'=>'Published','userid'=>$f['userId'],'dateCreated'=>date('Y-m-d H:i:s'),'updated'=>date('Y-m-d H:i:s'),'lastupdatedby'=>$f['userId'],'lastaccessed'=>date('Y-m-d H:i:s')]))throw new RuntimeException('Fixture context failed');
 $definitions=[];foreach(['','^Students','^Lecturers','^Guest'] as $suffix)$definitions[]=['name'=>$f['context'].$suffix,'description'=>'Synthetic sightings test'];$created=$groups->ensureGroups($definitions);if(empty($created['ok']))throw new RuntimeException('Fixture groups failed');foreach(['Students','Lecturers','Guest'] as $role)if(!$groups->ensureSubgroup($created['groups'][$f['context']],$created['groups'][$f['context'].'^'.$role]))throw new RuntimeException('Fixture hierarchy failed');
 }
 $store->create($f['context']);
 $f['groupId']=$e->getObject('groupadminmodel','groupadmin')->getLeafId([$f['context'],'Students']);
 $f['permissionId']=$e->getObject('identityservice','security')->permissionUserIdForUser($f['userId']);
 if(!$groups->ensureMembership($f['groupId'],$f['permissionId']))throw new RuntimeException('Fixture membership failed');file_put_contents($path,json_encode($f));
 echo "PASS local fixture prepared\n";exit;
}
$f=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$row=$users->findByUserId($f['userId']);
if(!$row||$row['username']!==$f['username']||!str_starts_with($f['username'],'sighting_test_')||!str_starts_with($f['context'],'sighttest'))throw new RuntimeException('Not the fixture');
if($op==='revoke'){$groups->removeMembership($f['groupId'],$f['permissionId']);echo "PASS membership revoked\n";}
elseif($op==='cleanup'){
 $users->setActive($f['userId'],false);$e->getObject('tokenauthservice','security')->revokeAllUserTokens($f['userId']);
 $store->transactionWithSavepoint(function()use($store,$groups,$f){$store->deleteForContext($f['context']);// Remove only this test's generated group records; never impersonate an administrator.
 $names=[$f['context'],$f['context'].'^Students',$f['context'].'^Lecturers',$f['context'].'^Guest'];
 $ids=array_column($store->selectPrepared('SELECT group_id FROM tbl_perms_groups WHERE group_define_name IN (?,?,?,?) FOR UPDATE',$names),'group_id');
 foreach($ids as $gid){
  foreach($store->selectPrepared('SELECT group_id,subgroup_id FROM tbl_perms_group_subgroups WHERE group_id=? OR subgroup_id=?',[$gid,$gid]) as $edge)if(!in_array($edge['group_id'],$ids)||!in_array($edge['subgroup_id'],$ids))throw new RuntimeException('Unexpected fixture relationship');
  foreach($store->selectPrepared('SELECT perm_user_id FROM tbl_perms_groupusers WHERE group_id=?',[$gid]) as $member)if($member['perm_user_id']!==$f['permissionId'])throw new RuntimeException('Unexpected fixture member');
 }
 foreach($ids as $gid)foreach(['tbl_perms_groupusers','tbl_perms_grouprights','tbl_perms_group_subgroups','tbl_perms_groups'] as $table)$store->executePrepared('DELETE FROM '.$table.' WHERE group_id=?',[$gid]);foreach(['tbl_contextmodules','tbl_context_blocks','tbl_context'] as $table)$store->executePrepared('DELETE FROM '.$table.' WHERE contextcode=?',[$f['context']]);});$store->afterContextDeletion();
 foreach(['tbl_rest_refresh_tokens','tbl_rest_api_keys','tbl_rest_identity_epoch'] as $table)$store->executePrepared('DELETE FROM '.$table.' WHERE user_id=?',[$f['userId']]);
 $store->executePrepared('DELETE FROM tbl_perms_perm_users WHERE perm_user_id=? AND auth_user_id=?',[$f['permissionId'],$f['userId']]);$store->executePrepared('DELETE FROM tbl_users WHERE userid=? AND id=?',[$f['userId'],$f['storageId']]);
 $e->getObject('indexdata','search')->removeIndex('context_contextcode_'.$f['context']);unlink($path);echo "PASS fixture cleaned\n";
}else exit(64);
