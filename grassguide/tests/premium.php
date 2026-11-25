<?php
foreach(['/framework/app/core_modules/restapi/sql/tbl_rest_api_keys.sql','/shared-modules/entitlement-service/sql/tbl_entitlement_service_grants.sql','/shared-modules/entitlement-service/sql/tbl_entitlement_service_revocations.sql','/shared-modules/account-event-service/sql/tbl_account_event_service_events.sql'] as $file){
    $tableIndexes=[];include $file;$schema->createTable($tablename,$fields,$options);foreach($tableIndexes as $name=>$index)$schema->createIndex($tablename,$name,$index);
}
$premium=$GLOBALS['objects']['grassguidepremium'];$grants=$GLOBALS['objects']['entitlementservice'];$tokens=$GLOBALS['objects']['tokenauthservice'];
ggCheck(!$premium->entitlement('fixture-admin')['premium'],'No premium by virtue of admin role');
ggReject(fn()=>$premium->create(['device_name'=>'Phone']),'premium_required');
$grant=$grants->grant(['userId'=>'fixture-admin','entitlementType'=>'premium','resourceType'=>'app','resourceId'=>'lowveld-grasses','sourceType'=>'manual','sourceReference'=>'fixture','idempotencyKey'=>'fixture-premium','correlationId'=>'fixture-premium','grantedByType'=>'system','grantedById'=>'fixture','metadata'=>['reason'=>'Fixture lifetime grant']]);
ggCheck($grant['ok'],'Canonical grant created');
$key=$premium->create(['device_name'=>'Phone']);$rows=$premium->keys();ggCheck(count($rows)===1&&!isset($rows[0]['key']),'Credential shown once only');
$r=$premium->response('GET',$key['key']);$v=json_decode($r['body'],true);
ggCheck($r['status']===200&&$v['premium']&&$v['lifetime']&&$v['offline_until']<=time()+30*86400,'Verified lifetime status with bounded offline lease');
ggCheck(!$premium->entitlement('fixture-other')['premium'],'No grant for other user');
ggCheck($premium->response('GET','bad')['status']===401,'Invalid code denied');
$wrong=$tokens->createApiKey('fixture-admin','Other',['profile:read']);ggCheck($premium->response('GET',$wrong['key'])['status']===401,'Wrong-scope credential denied');
$other=$tokens->createApiKey('fixture-other','Other',[grassguidepremium::SCOPE]);ggCheck(!json_decode($premium->response('GET',$other['key'])['body'],true)['premium'],'Scoped key cannot invent a grant');
$GLOBALS['objects']['userservice']->active=false;ggCheck($premium->response('GET',$key['key'])['status']===403,'Disabled account denied');$GLOBALS['objects']['userservice']->active=true;
ggCheck($premium->response('POST',$key['key'])['status']===405&&$premium->response('OPTIONS','')['status']===204,'Status read-only and preflight supported');
$species=$store->all()[0]['id'];$second=$store->all()[1]['id'];
$sync=fn($credential,$device,$ops)=>$premium->checklistResponse('POST',$credential,json_encode(['device'=>$device,'operations'=>$ops]));
$device=str_repeat('a',32);$device2=str_repeat('b',32);
$op=['seq'=>1,'id'=>$species,'checked'=>true];
$r=$sync($key['key'],$device,[$op]);ggCheck($r['status']===200&&json_decode($r['body'],true)['ids']===[$species],'Premium checklist saved');
$r=$sync($key['key'],$device2,[['seq'=>1,'id'=>$second,'checked'=>true]]);ggCheck(count(json_decode($r['body'],true)['ids'])===2,'Second device merges without overwriting');
$sync($key['key'],$device2,[['seq'=>2,'id'=>$species,'checked'=>false]]);
$r=$sync($key['key'],$device,[$op]);ggCheck(json_decode($r['body'],true)['ids']===[$second],'Lost-response retry does not replay an older change');
ggCheck($sync($other['key'],$device,[])['status']===403,'Non-premium account cannot read checklist');
ggCheck($sync($wrong['key'],$device,[])['status']===401,'Wrong scope cannot sync');
ggCheck($sync($key['key'],$device,[['seq'=>3,'id'=>$species,'checked'=>true]])['status']===422,'Sequence gaps rejected');
ggCheck($sync($key['key'],$device,[['seq'=>2,'id'=>str_repeat('f',32),'checked'=>true]])['status']===422,'Unknown species rejected atomically');
ggCheck($premium->checklistResponse('GET',$key['key'],'')['status']===405,'Checklist API requires explicit authenticated POST');
$premium->revoke(['key_id'=>(string)$key['id']]);ggCheck($premium->response('GET',$key['key'])['status']===401,'Revoked activation cannot renew');
$user->admin=false;ggReject(fn()=>$premium->create(['device_name'=>'Another']),'forbidden');$user->admin=true;
foreach(['createpremiumkey','revokepremiumkey'] as $action){$_GET=['action'=>$action];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===405,'Premium mutations POST only');$_SERVER['REQUEST_METHOD']='POST';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===403,'Premium mutations require CSRF');}
$_GET=[];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);
echo "PASS premium: real entitlement ledger and scoped API keys, no admin bypass, wrong-scope/other-account/disabled/revoked denial, bounded offline access, POST and CSRF.\n";
