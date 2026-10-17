<?php
if(PHP_SAPI!=='cli')exit(64);
chdir('/var/www/html/ch');
if(!str_contains(file_get_contents('config/dbdetails_inc.php'),'@editor-db/editor_fixture'))throw new RuntimeException('Disposable clone required');
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER += ['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'127.0.0.1:8088','SERVER_NAME'=>'127.0.0.1','SERVER_PORT'=>'8088','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>''];
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
set_exception_handler(static function($e){fwrite(STDERR,get_class($e).': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n");exit(1);});

$users=$engine->getObject('userservice','security');$groups=$engine->getObject('groupservice','groupadmin');$identities=$engine->getObject('identityservice','security');
foreach(['wg-peer'=>'wgpeer','wg-outsider'=>'wgoutsider','wg-teacher'=>'wgteacher'] as $id=>$name){
 if(!$users->findByUserId($id)){
  $r=$engine->getObject('userprovisioningservice','security')->createLocalUser(['userId'=>$id,'username'=>$name,'firstName'=>'Synthetic','surname'=>$name,'emailAddress'=>$name.'@example.invalid','howCreated'=>'useradmin'],'EditorFixture!2026-local');
  if(!$r['ok'])throw new RuntimeException('User fixture failed');
 }
}
foreach(['editor-fixture-member','wg-peer','wg-outsider','wg-teacher'] as $id){
 $role=$id==='wg-teacher'?'Lecturers':'Students';$gid=$groups->groupIdForName('admissionfree^'.$role);
 if(!$gid||!$groups->ensureMembership($gid,$identities->permissionUserIdForUser($id)))throw new RuntimeException('Course fixture failed');
}
$repo=$engine->getObject('sharingrepository','workgroup');
foreach(['wg-fixture'=>'admissionfree','wg-other'=>'foreign-context'] as $id=>$context){
 if(!$repo->group($id))$repo->saveGroup(['id'=>$id,'contextcode'=>$context,'description'=>'Synthetic '.$id,'creatorid'=>'wg-teacher'],['editor-fixture-member','wg-peer'],false);
}
$db=$engine->getObject('dbfile','filemanager');$folders=$engine->getObject('dbfolder','filemanager');
foreach(['editor-fixture-member','wg-outsider'] as $owner){
 $path='users/'.$owner;
 if(!$folders->getFolderId($path))$folders->insert(['folderpath'=>$path,'folderlevel'=>2,'folderstatus'=>'private','access'=>'public']);
}
foreach(['wg-file-public'=>['editor-fixture-member','public'],'wg-file-private'=>['editor-fixture-member','private_selected'],'wg-file-foreign'=>['wg-outsider','public']] as $id=>$details){
 [$owner,$access]=$details;
 if(!$db->getRow('id',$id))$db->insert(['id'=>$id,'userid'=>$owner,'creatorid'=>$owner,'modifierid'=>$owner,'filename'=>$id.'.txt','datatype'=>'txt','path'=>'users/'.$owner.'/'.$id.'.txt','filefolder'=>'users/'.$owner,'filesize'=>12,'mimetype'=>'text/plain','visibility'=>'visible','access'=>$access]);
}
$base=$engine->getObject('altconfig','config')->getcontentBasePath();
foreach(['wg-file-public','wg-file-private'] as $id){
 $root=$id==='wg-file-private'?$engine->getObject('dbsysconfig','sysconfig')->getValue('SECUREFODLER','filemanager'):$base;
 $directory=rtrim($root,'/').'/users/editor-fixture-member';
 if(!is_dir($directory)&&!mkdir($directory,0770,true))throw new RuntimeException('Fixture directory failed');
 file_put_contents($directory.'/'.$id.'.txt',"Synthetic workgroup file\n");
}
foreach(['wg-share-public'=>'wg-file-public','wg-share-private'=>'wg-file-private'] as $id=>$file){
 $repo->saveFile(['id'=>$id,'workgroupid'=>'wg-fixture','fileid'=>$file,'title'=>'Synthetic '.$id,'description'=>'Fixture description','version'=>'1','modifierid'=>'editor-fixture-member','datecreated'=>date('Y-m-d'),'datemodified'=>date('Y-m-d')],(bool)$repo->sharedFile($id));
}
$before=$repo->group('wg-fixture');
try{$repo->transactionWithSavepoint(function()use($repo){$repo->executePrepared('UPDATE tbl_workgroup SET description = ? WHERE id = ?',['rollback test','wg-fixture']);throw new RuntimeException('synthetic failure');});}catch(RuntimeException $e){if($e->getMessage()!=='synthetic failure')throw $e;}
if($repo->group('wg-fixture')!==$before)throw new RuntimeException('Rollback failed');
echo "PASS synthetic group/file fixtures and PDO rollback\n";
