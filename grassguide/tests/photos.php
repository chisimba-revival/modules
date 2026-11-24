<?php
$photos=$GLOBALS['objects']['grassguidephotos'];$user->admin=true;
$species=$service->save(bin2hex(random_bytes(16)),['revision'=>'0','genus'=>'Uploadus','epithet'=>'fixture','state'=>'draft','taxonomy_review'=>'pending','ecology_review'=>'pending']);
$id=$species['id'];
$base=['kind'=>'image','role'=>'other','state'=>'active','source_url'=>'https://example.org/source','image_url'=>'https://example.org/original.jpg','alt_text'=>'Original','caption'=>'Original','creator'=>'Original author','licence'=>'CC-BY-NC-4.0'];
$nc=$service->saveMedia($id,bin2hex(random_bytes(16)),['revision'=>'0']+$base);
$cc=$service->saveMedia($id,bin2hex(random_bytes(16)),array_merge($base,['revision'=>'0','licence'=>'CC-BY-4.0']));
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aV1EAAAAASUVORK5CYII=');
$in=['revision'=>(string)$species['revision'],'captions'=>'Whole plant, "Head, close up"','creator'=>'Fixture Photographer','licence'=>'CC-BY-4.0','photo_mode'=>'replace_nc'];
ggCheck(grassguidephotos::captions($in['captions'],2)===['Whole plant','Head, close up'],'CSV quoted caption');
ggReject(fn()=>grassguidephotos::captions('One',2),'caption_count');
$user->admin=false;ggReject(fn()=>$photos->saveBatch($id,$in,[$png,$png]),'forbidden');$user->admin=true;
ggReject(fn()=>$photos->saveBatch($id,$in,[$png,'<svg>bad</svg>']),'photo_files');
ggCheck($service->mediaOne($nc['id'])['state']==='active','Invalid batch does not archive');
// Fail after files are stored and after the first media mutation. Rollback includes archival and policy.
$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_photo BEFORE INSERT ON tbl_grassguide_audit FOR EACH ROW BEGIN IF NEW.action='species_saved' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'; END IF; END");
$failed=false;try{$photos->saveBatch($id,$in,[$png,$png]);}catch(Throwable $e){$failed=true;}
$store->executeExactPrepared('DROP TRIGGER grassguide_fail_photo');
ggCheck($failed&&count($service->mediaFor($id))===2&&$service->mediaOne($nc['id'])['state']==='active','Batch rolls back media and archive');
ggCheck(!$store->selectExactPrepared('SELECT id FROM tbl_files')&&!glob('/photo-store/moduleassets/grassguide/*'),'Unlinked stored files compensated');
$species=$photos->saveBatch($id,$in,[$png,$png]);
ggCheck(!empty($species['_block_nc_images'])&&$service->mediaOne($nc['id'])['state']==='archived'&&$service->mediaOne($cc['id'])['state']==='active','NC only archived and future imports blocked');
ggReject(fn()=>$photos->saveBatch($id,$in,[$png,$png]),'conflict');
$own=array_values(array_filter($service->mediaFor($id),fn($m)=>!empty($m['_file_id'])));ggCheck(count($own)===2&&$photos->read($own[0]['id'])===$png,'Stored image bytes read through File Manager');
$edited=$service->saveMedia($id,$own[0]['id'],array_merge($own[0],['caption'=>'Edited caption']));ggCheck($photos->read($edited['id'])===$png,'Editing caption retains owned file binding');
$species=$service->save($id,$species);ggCheck(!empty($species['_block_nc_images']),'Species edit preserves import block');
$job=['id'=>md5($id.':inaturalist'),'species_id'=>$id,'provider'=>'inaturalist'];
$result=['name'=>$species['scientific_name'],'fields'=>[],'sources'=>[],'matched'=>true,'media'=>[array_merge($base,['external_id'=>'new-nc']),array_merge($base,['external_id'=>'new-cc','licence'=>'CC0-1.0'])]];
$store->applyEnrichment($job,$result);ggCheck(!$service->mediaOne(md5($id.':new-nc'))&&$service->mediaOne(md5($id.':new-cc')),'In-flight results reject new NC but keep CC0');
$policy=$rights->policy();$rights->savePolicy(['revision'=>$policy['revision'],'mode'=>'noncommercial','app_updates'=>'1']);
$user->logged=false;ggCheck($photos->read($own[0]['id'])===$png,'Anonymous app reader can fetch published image');
$user->logged=true;$archived=$service->saveMedia($id,$own[0]['id'],array_merge($edited,['state'=>'archived']));
$user->logged=false;ggReject(fn()=>$photos->read($archived['id']),'not_found');$user->logged=true;
$species=$service->one($id);$species=$photos->resume($id,['revision'=>(string)$species['revision']]);
ggCheck(empty($species['_block_nc_images'])&&$service->mediaOne($nc['id'])['state']==='archived','Resume permits future imports without restoring archives');
$store->applyEnrichment($job,$result);ggCheck($service->mediaOne(md5($id.':new-nc'))!==null,'New NC imports allowed after resume');
foreach(['uploadphotos','resumenc'] as $action){
 $_GET=['action'=>$action,'id'=>$id];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===405,'Photo writes require POST');
 $_SERVER['REQUEST_METHOD']='POST';http_response_code(200);$c=new grassguide();$c->dispatch();ggCheck(http_response_code()===403,'Photo writes require CSRF');
}
$_GET=[];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);
echo "PASS photo batches: quoted captions, File Manager bytes, upload validation, transactional replacement, file cleanup, stale retry, retained bindings, worker block/resume, publication access, method and CSRF.\n";
