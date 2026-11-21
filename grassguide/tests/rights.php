<?php
$rights=$GLOBALS['objects']['grassguiderights'];$rights->init();
ggCheck($rights->policy()['mode']==='noncommercial','NC remains the default');
$sourceSpecies=$service->all()[0];
$nc=['revision'=>'0','kind'=>'image','role'=>'habit','state'=>'active','source_url'=>'https://example.org/nc','image_url'=>'https://example.org/nc.jpg','caption'=>'NC fixture','alt_text'=>'Grass','creator'=>'Fixture author','licence'=>'CC-BY-NC-4.0'];
$nc=$service->saveMedia($sourceSpecies['id'],bin2hex(random_bytes(16)),$nc);
$target='media:'.$nc['id'];$item=$rights->target($target);
ggCheck(grassguiderights::eligible($item,'noncommercial')&&!grassguiderights::eligible($item,'commercial'),'NC mode filtering');
$task=['revision'=>'0','fingerprint'=>$item['fingerprint'],'task_status'=>'resolved','decision'=>'inherit','assignee'=>'Fixture owner','due_date'=>'2027-01-01','task_notes'=>'PRIVATE NOTES'];
$task=$rights->saveTask($target,$task);ggCheck(!grassguiderights::eligible($rights->target($target),'commercial'),'Resolved is not permission');
ggReject(fn()=>$rights->saveTask($target,array_merge($task,['revision'=>0])),'conflict');
ggReject(fn()=>$rights->saveTask($target,array_merge($task,['decision'=>'commercial_permission'])),'permission_required');
$task=$rights->saveTask($target,array_merge($task,['decision'=>'commercial_permission','permission_evidence'=>'PRIVATE CONTRACT','public_credit'=>'Commercial distribution permitted by Fixture author.']));
ggCheck(grassguiderights::eligible($rights->target($target),'commercial'),'Evidence-backed permission');
$export=$rights->appExport('commercial');$json=json_encode($export);ggCheck(!str_contains($json,'PRIVATE CONTRACT')&&!str_contains($json,'PRIVATE NOTES')&&!str_contains($json,'Fixture owner'),'Private evidence omitted');
ggCheck(str_contains($json,'Commercial distribution permitted by Fixture author.'),'Public permission retained');
$nc['image_url']='https://example.org/changed.jpg';$service->saveMedia($sourceSpecies['id'],$nc['id'],$nc);
ggCheck($rights->target($target)['stale']&&!grassguiderights::eligible($rights->target($target),'commercial'),'Changed content invalidates permission');
$item=$rights->target($target);$task=$rights->saveTask($target,array_merge($task,['fingerprint'=>$item['fingerprint'],'decision'=>'exclude']));
ggCheck(!grassguiderights::eligible($rights->target($target),'noncommercial'),'Explicit exclusions apply even in NC mode');
$policy=$rights->savePolicy(['revision'=>0,'mode'=>'commercial']);ggCheck($rights->appExport()['publication_mode']==='commercial','Saved policy controls export');
ggReject(fn()=>$rights->savePolicy(['revision'=>0,'mode'=>'noncommercial']),'conflict');
ggReject(fn()=>$rights->savePolicy(['revision'=>$policy['revision'],'mode'=>'invalid']),'invalid');
$rights->savePolicy(['revision'=>$policy['revision'],'mode'=>'noncommercial']);
// Preview does not modify the stored policy.
$rights->appExport('commercial');ggCheck($rights->policy()['mode']==='noncommercial','Preview is read only');
foreach(['rights','rightstask','savepolicy','saverights','appexport','commercialpreview'] as $action){$user->admin=false;$_GET=['action'=>$action,'target'=>$target];$_POST=[];http_response_code(200);$c=new grassguide();ggCheck($c->dispatch()==='denied_tpl.php'&&http_response_code()===403,'Admin boundary '.$action);}
foreach([fn()=>$rights->inventory(),fn()=>$rights->appExport(),fn()=>$rights->policy(),fn()=>$rights->saveTask($target,$task)] as $call)ggReject($call,'forbidden');$user->admin=true;
foreach(['savepolicy','saverights'] as $action){$_GET=['action'=>$action,'target'=>$target];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===405,'GET rights mutation denied');$_POST=['csrf_token'=>'bad'];$_SERVER['REQUEST_METHOD']='POST';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===403,'Rights CSRF denied');}
foreach(['rights','rightstask'] as $action){$_GET=['action'=>$action,'target'=>$target];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$template=$c->dispatch();ob_start();$c->render($template);$html=ob_get_clean();ggCheck(!preg_match('/mod_grassguide_[a-z_]+/',$html),'All rights labels registered');ggCheck(str_contains($html,'Rights and publication'),'Rights page rendered');}
$before=$store->rightsOne($target);$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_rights_audit BEFORE INSERT ON tbl_grassguide_audit FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'");
$failed=false;try{$rights->saveTask($target,array_merge($task,['task_status'=>'planned']));}catch(Throwable $ex){$failed=true;}$store->executeExactPrepared('DROP TRIGGER grassguide_fail_rights_audit');ggCheck($failed&&$before===$store->rightsOne($target),'Rights audit failure rolls back');
echo "PASS rights: NC default, policy/preview, private export, permissions, stale content, concurrency, rollback, CSRF, admin-only access and templates.\n";

$user->admin=false;
ggCheck($rights->publicResponse('GET')['status']===404,'Public catalogue disabled by default');
ggCheck($rights->publicResponse('POST')['status']===405,'Public catalogue read only');
$user->admin=true;$p=$rights->policy();$rights->savePolicy(['revision'=>$p['revision'],'mode'=>'noncommercial','app_updates'=>'1']);$user->admin=false;
$r=$rights->publicResponse('GET');ggCheck($r['status']===200,'Anonymous filtered catalogue available');
ggCheck(!str_contains($r['body'],'PRIVATE')&&!str_contains($r['body'],'Fixture owner'),'No private rights evidence in public feed');
ggCheck($rights->publicResponse('GET',$r['headers']['ETag'])['status']===304,'Stable content ETag');
ggCheck($rights->publicResponse('HEAD')['body']===''&&$rights->publicResponse('OPTIONS')['status']===204,'HEAD and CORS preflight');
ggReject(fn()=>$rights->inventory(),'forbidden');ggReject(fn()=>$rights->policy(),'forbidden');
$user->admin=true;$p=$rights->policy();$rights->savePolicy(['revision'=>$p['revision'],'mode'=>'commercial','app_updates'=>'1']);$user->admin=false;
$paid=$rights->publicResponse('GET',$r['headers']['ETag']);ggCheck($paid['status']===200&&!str_contains($paid['body'],'CC-BY-NC'),'Policy change invalidates ETag and removes NC');
$c=new grassguide();ggCheck(!$c->requiresLogin('catalogue')&&$c->requiresLogin('rights')&&$c->requiresLogin('appexport'),'Only catalogue is anonymous');
$user->admin=true;$p=$rights->policy();$rights->savePolicy(['revision'=>$p['revision'],'mode'=>'noncommercial']);
ggCheck($rights->publicResponse('GET',$paid['headers']['ETag'])['status']===404,'Unpublishing overrides cached ETag');
echo "PASS public catalogue: opt-in, methods, CORS, ETag, filtering, private data and restored guards.\n";
