<?php
$species=array_values(array_filter($service->all(),fn($s)=>$s['state']!=='archived'))[0];$name=$species['scientific_name'];
$apis->responses=[['results'=>[['id'=>99,'name'=>$name,'rank'=>'species','is_active'=>true,'ancestor_ids'=>[47434]]]],['results'=>[['id'=>99,'name'=>$name,'is_active'=>true,'taxon_photos'=>[['photo'=>['id'=>989,'license_code'=>'cc-by','attribution_name'=>'Synthetic owner','large_url'=>'https://example.org/989/large.jpg']]]]]],['results'=>[
 ['id'=>1001,'quality_grade'=>'needs_id','photos'=>[['id'=>989]]],
 ['id'=>1002,'quality_grade'=>'research','photos'=>[['id'=>12345]]]
]]];
$result=$apis->fetch('inaturalist',$species);ggCheck($result['photo_grades'][989]['quality_grade']==='needs_id','Grade belongs only to exact photo ID');
$job=['id'=>md5($species['id'].':inaturalist'),'species_id'=>$species['id'],'provider'=>'inaturalist'];$store->applyEnrichment($job,$result);
$id=md5($species['id'].':inaturalist:989');$media=$service->mediaOne($id);ggCheck($media['_inat']['observations'][0]['id']==='1001','Observation provenance persisted');
$media['caption']='My identification note';$media=$service->saveMedia($species['id'],$id,$media);ggCheck($media['_inat']['quality_grade']==='needs_id'&&$media['_external_id']==='inaturalist:989','Manual edits retain provenance');
$old=$media;$media['state']='excluded';$media['exclusion_reason']='Wrong species — synthetic test';
$user->admin=false;ggReject(fn()=>$service->saveMedia($species['id'],$id,$media),'forbidden');$user->admin=true;
$blank=$media;$blank['exclusion_reason']='';$failed=false;try{$service->saveMedia($species['id'],$id,$blank);}catch(DomainException $e){$failed=true;}ggCheck($failed,'Exclusion reason required');
$media=$service->saveMedia($species['id'],$id,$media);ggReject(fn()=>$service->saveMedia($species['id'],$id,$old),'conflict');
$result['media'][0]['external_id']='alternative-import-id';$result['media'][0]['image_url']='https://example.org/989/medium.jpg';$store->applyEnrichment($job,$result);
ggCheck(!$service->mediaOne(md5($species['id'].':alternative-import-id')),'Excluded photo cannot return through another import ID or image size');
$bundle=$rights->appExport();$export=array_values(array_filter($bundle['species'],fn($s)=>$s['id']===$species['id']))[0];
ggCheck(!array_filter($export['media'],fn($m)=>str_contains($m['source_url'],'/989')),'Excluded photo absent from public export');
$media['state']='active';$media=$service->saveMedia($species['id'],$id,$media);$fingerprint=$rights->target('media:'.$id)['fingerprint'];$result['photo_grades'][989]['quality_grade']='research';$result['photo_grades'][989]['observations'][0]['quality_grade']='research';$result['media']=[];$store->applyEnrichment($job,$result);
ggCheck($rights->target('media:'.$id)['fingerprint']===$fingerprint,'Grade changes do not invalidate rights evidence');
$media=$service->mediaOne($id);ggCheck($media['caption']==='My identification note'&&$media['_inat']['quality_grade']==='research','Grade refresh preserves captions');
$bundle=$rights->appExport();$export=array_values(array_filter($bundle['species'],fn($s)=>$s['id']===$species['id']))[0];$published=array_values(array_filter($export['media'],fn($m)=>str_contains($m['source_url'],'/989')))[0];ggCheck($published['identification']['quality_grade']==='research'&&!isset($published['exclusion_reason']),'Grade public; review reason private');
$_GET=['action'=>'media','id'=>$id,'flag'=>'1'];$_POST=[];$c=new grassguide();$template=$c->dispatch();ob_start();$c->render($template);$html=ob_get_clean();ggCheck(str_contains($html,'exclusion_reason')&&str_contains($html,'1001')&&!preg_match('/mod_grassguide_[a-z_]+/',$html),'Review UI and observation link translated');$_GET=[];
echo "PASS image review: exact photo grades, provenance, private exclusion reasons, permissions, stale saves, re-import blocking, restoration and public projection.\n";
