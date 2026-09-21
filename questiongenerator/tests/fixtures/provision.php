<?php
// Disposable local fixture only; never expose as a web endpoint.
if(PHP_SAPI!=='cli' || getenv('QUESTIONWORKSHOP_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$e=new engine();
$tag='qwqa'.bin2hex(random_bytes(4));$password=bin2hex(random_bytes(16)).'Aa!';$provision=$e->getObject('userprovisioningservice','security');$users=$e->getObject('userservice','security');$group=$e->getObject('groupservice','groupadmin');
$f=['tag'=>$tag,'password'=>$password,'users'=>[],'ids'=>[]];
foreach(['teacher','other','student'] as $role){$id=$users->generateUserId();$name=$tag.$role;$result=$provision->createLocalUser(['userId'=>$id,'username'=>$name,'firstName'=>'Workshop QA','surname'=>$role,'emailAddress'=>$name.'@example.invalid','isActive'=>1,'howCreated'=>'mcqgenerator-fixture'],$password);if(empty($result['ok']))throw new RuntimeException($result['code']);if($role!=='student')$group->ensureMembership($group->groupIdForName('Lecturers'),$e->getObject('identityservice','security')->ensurePermissionIdentity($id));$f['users'][$role]=$name;$f['ids'][$role]=$id;}
file_put_contents('/tmp/workshop-fixture.json',json_encode($f));chmod('/tmp/workshop-fixture.json',0600);echo "Disposable users ready.\n";
