<?php
$user->admin=true;
$species=$service->save(bin2hex(random_bytes(16)),['revision'=>'0','genus'=>'Copyrightus','epithet'=>'fixture','state'=>'draft','taxonomy_review'=>'pending','ecology_review'=>'pending']);$id=$species['id'];
$input=['revision'=>(string)$species['revision'],'captions'=>'Copyright photograph','creator'=>'Fixture Owner','licence'=>'copyright','photo_mode'=>'add'];
ggReject(fn()=>$photos->saveBatch($id,$input,[$png]),'copyright_required');
ggReject(fn()=>$photos->saveBatch($id,$input+['copyright_confirm'=>'1'],[$png]),'invalid');
$input+=['copyright_confirm'=>'1','copyright_evidence'=>'PRIVATE permission fixture: owner authorises free and paid app.'];
$filesBefore=$store->selectExactPrepared('SELECT id FROM tbl_files ORDER BY id');
$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_copyright BEFORE INSERT ON tbl_grassguide_audit FOR EACH ROW BEGIN IF NEW.action='rights_saved' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'; END IF; END");
$failed=false;try{$photos->saveBatch($id,$input,[$png]);}catch(Throwable $e){$failed=true;}
$store->executeExactPrepared('DROP TRIGGER grassguide_fail_copyright');
ggCheck($failed&&!$service->mediaFor($id)&&$filesBefore===$store->selectExactPrepared('SELECT id FROM tbl_files ORDER BY id'),'Copyright permission failure rolls back upload and files');
$photos->saveBatch($id,$input,[$png]);$image=$service->mediaFor($id)[0];$item=$rights->target('media:'.$image['id']);
ggCheck($image['licence']==='copyright'&&$item['task']['decision']==='commercial_permission'&&$item['task']['permission_evidence']===$input['copyright_evidence'],'Copyright and private permission retained separately');
foreach(['noncommercial','commercial'] as $mode){$export=$rights->operatorExport($mode);$record=array_values(array_filter($export['species'],fn($s)=>$s['id']===$id))[0];$media=$record['media'][0];ggCheck($media['licence']==='permission'&&$media['original_licence']==='copyright'&&str_contains($media['permission_credit'],'© Fixture Owner'),'Copyright credit exported through recorded permission');ggCheck(!str_contains(json_encode($export),'PRIVATE permission fixture'),'Permission evidence stays private');}
$uncleared=$item;$uncleared['task']['decision']='inherit';ggCheck(!grassguiderights::eligible($uncleared,'commercial')&&!grassguiderights::eligible($uncleared,'noncommercial'),'Copyright alone is not publication permission');
$edited=$service->saveMedia($id,$image['id'],array_replace($image,['caption'=>'Changed work']));ggCheck(!grassguiderights::eligible($rights->target('media:'.$image['id']),'commercial'),'Changed copyrighted work invalidates old permission');
echo "PASS copyright upload: explicit permission, private evidence, publication credits, atomic rollback and stale permission checks.\n";
