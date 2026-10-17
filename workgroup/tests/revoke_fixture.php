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

$repo=$engine->getObject('sharingrepository','workgroup');
$mode=$argv[1]??'';
if($mode==='group')$repo->executePrepared('DELETE FROM tbl_workgroup_users WHERE workgroupid=? AND userid=?',['wg-fixture','editor-fixture-member']);
elseif($mode==='restoregroup')$repo->saveGroup($repo->group('wg-fixture'),['editor-fixture-member','wg-peer'],true);
else {
 $id=$mode==='teacher'?'wg-teacher':'editor-fixture-member';
 $role=$mode==='teacher'?'Lecturers':'Students';
 $groups->removeMembership($groups->groupIdForName('admissionfree^'.$role),$identities->permissionUserIdForUser($id));
}
echo "PASS revoked fixture access\n";
