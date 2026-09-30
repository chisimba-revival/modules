<?php
if(PHP_SAPI!=='cli'||getenv('MCQBANK_LOCAL_TEST')!=='1')exit(64);
// The caller must explicitly select a disposable runtime. Never use a live installation.
$runtime=getenv('MCQBANK_TEST_RUNTIME');if(!$runtime||!is_file($runtime.'/.mcq-bank-disposable'))exit(64);
chdir($runtime);$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost:8097';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$e=new engine();
$mf=$e->getObject('modulefile','modulecatalogue');$ma=$e->getObject('modulesadmin','modulecatalogue');
foreach(['mcqtests','questiongenerator'] as $module){$data=$mf->readRegisterFile($mf->findRegisterFile($module));$result=$ma->installModule($data,true);echo $module.': '.json_encode($result)."\n";}
$users=$e->getObject('userservice','security');$provision=$e->getObject('userprovisioningservice','security');$groups=$e->getObject('groupservice','groupadmin');
$f=['tag'=>'bankqa'.bin2hex(random_bytes(3)),'users'=>[],'ids'=>[],'password'=>bin2hex(random_bytes(12)).'Aa!'];
foreach(['admin','teacher','student','other'] as $role){
$id=$users->generateUserId();$username=$f['tag'].$role;
$r=$provision->createLocalUser(['userId'=>$id,'username'=>$username,'firstName'=>'Bank QA','surname'=>$role,'emailAddress'=>$username.'@example.invalid','isActive'=>1,'howCreated'=>'mcq-bank-disposable-test'],$f['password']);if(empty($r['ok']))throw new RuntimeException('User fixture failed');
if($role!=='student')$groups->ensureMembership($groups->groupIdForName($role==='admin'?'Site Admin':'Lecturers'),$e->getObject('identityservice','security')->ensurePermissionIdentity($id));
$f['users'][$role]=$username;$f['ids'][$role]=$id;
}
file_put_contents('/tmp/mcq-bank-fixture.json',json_encode($f));chmod('/tmp/mcq-bank-fixture.json',0600);echo "Disposable users ready.\n";
