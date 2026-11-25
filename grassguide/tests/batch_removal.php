<?php
$parent=$species['id'];$first=$service->mediaOne($id);
$secondId=md5('batch-removal-test');$copy=$first;unset($copy['id'],$copy['revision'],$copy['species_id'],$copy['created_at'],$copy['updated_at']);$copy['source_url']='https://example.org/batch-photo';$copy['image_url']='https://example.org/batch.jpg';
$store->writeMedia($parent,$secondId,0,$copy,'test');$second=$service->mediaOne($secondId);
$selection=['selected'=>[$id.':'.$first['revision'],$secondId.':'.$second['revision']]];
$user->admin=false;ggReject(fn()=>$service->removeMediaBatch($parent,$selection),'forbidden');$user->admin=true;
ggReject(fn()=>$service->removeMediaBatch($parent,[]),'invalid');
ggReject(fn()=>$service->removeMediaBatch($parent,['selected'=>['bad']]),'invalid');
ggReject(fn()=>$service->removeMediaBatch($parent,['selected'=>[$selection['selected'][0],$selection['selected'][0]]]),'invalid');
$stale=$selection;$stale['selected'][1]=$secondId.':999';ggReject(fn()=>$service->removeMediaBatch($parent,$stale),'conflict');
ggCheck($service->mediaOne($id)===$first,'Stale batch rolls back earlier writes');
$service->removeMediaBatch($parent,$selection);
foreach([$id,$secondId] as $removed)ggCheck($service->mediaOne($removed)['state']==='excluded','Batch excludes each selected photo');
ggCheck($service->mediaOne($id)['_inat']===$first['_inat'],'Batch preserves provenance');
$bundle=$rights->appExport();$export=array_values(array_filter($bundle['species'],fn($s)=>$s['id']===$parent))[0];ggCheck(!array_filter($export['media'],fn($m)=>in_array($m['id'],[$id,$secondId],true)),'Batch images omitted from export');
foreach([$id,$secondId] as $removed){$m=$service->mediaOne($removed);$m['state']='active';$service->saveMedia($parent,$removed,$m);}
$_GET=['action'=>'edit','id'=>$parent];$_POST=[];$c=new grassguide();$template=$c->dispatch();ob_start();$c->render($template);$html=ob_get_clean();ggCheck(str_contains($html,'selected[]')&&str_contains($html,'Remove and do not reload')&&!preg_match('/mod_grassguide_[a-z_]+/',$html),'Batch editor translated');$_GET=[];
echo "PASS batch removal: permissions, selection validation, atomic stale rollback, provenance, public exclusion and restoration.\n";
