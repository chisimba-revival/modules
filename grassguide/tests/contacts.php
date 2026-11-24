<?php
$contacts=$GLOBALS['objects']['grassguidecontacts'];$user->admin=true;
$groups=$rights->ncContributors();$id=$groups[0]['id'];$contact=$contacts->contact($id);
ggCheck($contact['revision']===0 && str_contains($contact['request_body'],'paid access'),'Default request explains paid and offline use');
ggReject(fn()=>$contacts->save($id,array_merge($contact,['email'=>'bad address'])),'contact_email_invalid');
$contact=$contacts->save($id,array_merge($contact,['email'=>'photographer@example.test','opening'=>'PRIVATE PERSONAL INTRO','signature'=>'Fixture Administrator']));
$preview=$contacts->preview($id);ggCheck(str_contains($preview['body'],'PRIVATE PERSONAL INTRO')&&str_contains($preview['body'],$groups[0]['items'][0]['source_url']),'Preview includes personal introduction and source links');
$input=['revision'=>$contact['revision'],'fingerprint'=>$preview['fingerprint'],'request_id'=>bin2hex(random_bytes(16))];
$user->admin=false;foreach([fn()=>$contacts->contact($id),fn()=>$contacts->save($id,$contact),fn()=>$contacts->queue($id,$input),fn()=>$contacts->history($id)] as $call)ggReject($call,'forbidden');$user->admin=true;
ggReject(fn()=>$contacts->queue($id,array_merge($input,['fingerprint'=>str_repeat('0',64)])),'conflict');
// Force failure after the outbox insert: contacts, mail and request audit must all roll back.
$before=count($store->selectExactPrepared('SELECT id FROM tbl_communications_outbox'));
$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_request BEFORE INSERT ON tbl_grassguide_rights FOR EACH ROW BEGIN IF NEW.target LIKE 'request:%' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'; END IF; END");
$failed=false;try{$contacts->queue($id,$input);}catch(Throwable $e){$failed=true;}
$store->executeExactPrepared('DROP TRIGGER grassguide_fail_request');
ggCheck($failed && count($store->selectExactPrepared('SELECT id FROM tbl_communications_outbox'))===$before && $contacts->contact($id)['revision']===$contact['revision'],'Failed request rolls back shared outbox and contact revision');
$queued=$contacts->queue($id,$input);$mail=$communication->status($queued['message_id']);
ggCheck($mail['recipient']==='photographer@example.test' && $mail['sender']==='sender@example.test' && $mail['body_text']===$preview['body'] && $mail['status']==='queued','Exact preview queued through real communication service');
ggCheck($contacts->queue($id,$input)['message_id']===$queued['message_id'] && count($store->selectExactPrepared('SELECT id FROM tbl_communications_outbox'))===$before+1,'Repeat request is idempotent');
$new=$contacts->preview($id);ggReject(fn()=>$contacts->queue($id,['revision'=>$new['revision'],'fingerprint'=>$new['fingerprint'],'request_id'=>bin2hex(random_bytes(16))]),'permission_already_pending');
$contact=$contacts->contact($id);$contacts->save($id,array_merge($contact,['opening'=>'Changed draft']));ggCheck($communication->status($queued['message_id'])['body_text']===$preview['body'],'Draft changes cannot alter queued message');
$export=json_encode($rights->appExport());ggCheck(!str_contains($export,'photographer@example.test')&&!str_contains($export,'PRIVATE PERSONAL INTRO'),'Private contacts and correspondence excluded from public catalogue');
foreach(['savecontact','queuepermission'] as $action) {
 $_GET=['action'=>$action,'id'=>$id];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===405,'Contact mutation requires POST');
 $_POST=['csrf_token'=>'bad'];$_SERVER['REQUEST_METHOD']='POST';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===403,'Contact mutation requires CSRF');
}
$_GET=['action'=>'contributors','contributor'=>$id];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$template=$c->dispatch();ob_start();$c->render($template);$html=ob_get_clean();ggCheck(!preg_match('/mod_grassguide_[a-z_]+/',$html)&&str_contains($html,'Permission-request history'),'Contact UI and delivery status render');
echo "PASS permission requests: private drafts, exact previews, sender configuration, transaction rollback including real shared outbox, idempotency, pending guard, snapshot preservation, privacy, method/CSRF and permissions. No transport invoked.\n";
$species=$service->one($groups[0]['items'][0]['species_id']);
$archived=$service->setMembership($species['id'],['revision'=>$species['revision'],'operation'=>'remove']);
ggCheck(count($contacts->group($id)['items'])===0 && count($contacts->history($id))===1,'Saved contact and history survive archived images');
$service->setMembership($species['id'],['revision'=>$archived['revision'],'operation'=>'restore']);
// A separate synthetic contact remains available for the browser workflow.
$service->saveMedia($groups[0]['items'][0]['species_id'],bin2hex(random_bytes(16)),['revision'=>'0','kind'=>'image','role'=>'habit','state'=>'active','source_url'=>'https://example.org/preview-source','image_url'=>'https://example.org/preview-image.jpg','alt_text'=>'Synthetic grass photograph','creator'=>'Preview contributor','licence'=>'CC-BY-NC-4.0']);
