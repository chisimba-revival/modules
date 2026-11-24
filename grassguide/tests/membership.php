<?php
// These fixtures run after the existing fixed-size seed checks.
$user->admin=true;
$new=$service->save(bin2hex(random_bytes(16)),['revision'=>'0','genus'=>'Fixtureus','epithet'=>'regionalis','state'=>'draft','taxonomy_review'=>'pending','ecology_review'=>'pending']);
ggCheck($new['common_name']==='' && $new['state']==='draft','Scientific name alone creates a species');
$id=$new['id'];
$photo=$service->saveMedia($id,bin2hex(random_bytes(16)),['revision'=>'0','kind'=>'image','role'=>'habit','state'=>'active','source_url'=>'https://example.org/fixture','image_url'=>'https://example.org/photo.jpg','alt_text'=>'Fixture grass','creator'=>'Fixture','licence'=>'CC-BY-4.0']);
$service->queueEnrichment();ggCheck(count($service->enrichmentJobs($id))===5,'New species receives provider jobs');
$beforeMedia=$service->mediaFor($id);
$present=fn()=>in_array($id,array_column($rights->appExport()['species'],'id'),true);
ggCheck($present(),'New draft is included in app catalogue');
$user->admin=false;ggReject(fn()=>$service->setMembership($id,['revision'=>$new['revision'],'operation'=>'remove']),'forbidden');$user->admin=true;
ggReject(fn()=>$service->setMembership($id,['revision'=>$new['revision'],'operation'=>'delete']),'invalid');
$removed=$service->setMembership($id,['revision'=>$new['revision'],'operation'=>'remove']);
ggCheck(!$present() && $service->mediaFor($id)===$beforeMedia,'Removal hides species while retaining photos');
ggReject(fn()=>$service->setMembership($id,['revision'=>$new['revision'],'operation'=>'remove']),'conflict');
ggReject(fn()=>$service->save(bin2hex(random_bytes(16)),array_merge($new,['revision'=>'0'])),'duplicate');
$restored=$service->setMembership($id,['revision'=>$removed['revision'],'operation'=>'restore']);
ggCheck($present() && $restored['state']==='draft' && $restored['id']===$id && $service->mediaFor($id)===$beforeMedia,'Restore preserves identity and media');
$seed=$service->all()[0];$service->setMembership($seed['id'],['revision'=>$seed['revision'],'operation'=>'remove']);
ggCheck($service->import($service->seedPreview()['sha256'])===0 && $service->one($seed['id'])['state']==='archived','Seed replay cannot restore removal');
$before=$service->one($id);
$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_membership BEFORE INSERT ON tbl_grassguide_audit FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'");
$failed=false;try{$service->setMembership($id,['revision'=>$before['revision'],'operation'=>'remove']);}catch(Throwable $ex){$failed=true;}
$store->executeExactPrepared('DROP TRIGGER grassguide_fail_membership');
ggCheck($failed && $service->one($id)===$before,'Membership and audit roll back together');
foreach(['membership','setmembership'] as $action){$user->admin=false;$_GET=['action'=>$action,'id'=>$id];$_POST=[];http_response_code(200);$c=new grassguide();ggCheck($c->dispatch()==='denied_tpl.php'&&http_response_code()===403,'Membership admin guard');}$user->admin=true;
$_GET=['action'=>'setmembership','id'=>$id];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===405,'Membership requires POST');
$_POST=['csrf_token'=>'bad','revision'=>(string)$before['revision'],'operation'=>'remove'];$_SERVER['REQUEST_METHOD']='POST';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===403 && $service->one($id)===$before,'Membership requires CSRF');
foreach(['edit','membership'] as $action){$_GET=['action'=>$action];if($action==='membership')$_GET['id']=$id;$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$template=$c->dispatch();ob_start();$c->render($template);$html=ob_get_clean();ggCheck(!preg_match('/mod_grassguide_[a-z_]+/',$html),'Membership labels registered');}
echo "PASS species membership: minimal creation, worker queue, removal/export, restore/media, seed replay, duplicates, stale revisions, rollback, permissions and CSRF.\n";
