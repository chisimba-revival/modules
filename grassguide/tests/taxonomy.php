<?php
/** Real-PDO reconciliation: preservation, retry, rollback and legacy offline IDs. */
$user->admin=true;
$make=fn($epithet)=>$service->save(md5('taxonomy-'.$epithet),['revision'=>'0','genus'=>'Taxonomicus','epithet'=>$epithet,'authority'=>'Fixture','state'=>'draft','taxonomy_review'=>'pending','ecology_review'=>'pending']);
$old=$make('old');$target=$make('accepted');$unknown=$make('unknown');
$photo=$service->saveMedia($old['id'],md5('taxonomy-photo'),['revision'=>'0','kind'=>'image','role'=>'habit','state'=>'active','creator'=>'Fixture','alt_text'=>'Fixture','source_url'=>'https://example.org/taxon','image_url'=>'https://example.org/taxon.jpg','licence'=>'CC-BY-4.0']);
$old=$service->save($old['id'],array_replace($old,['representative_image'=>$photo['id'],'similar'=>'Keep this editorial record.']));
$plan=['version'=>'fixture-v1','source'=>'Fixture POWO','entries'=>[]];
foreach([$old,$target,$unknown] as $row)$plan['entries'][]=['original_name'=>$row['scientific_name'],'original_authority'=>'Fixture','name'=>$row['id']===$old['id']?$target['scientific_name']:$row['scientific_name'],'authority'=>'Fixture','status'=>$row['id']===$unknown['id']?'unresolved':'accepted','powo_id'=>$row['id']===$unknown['id']?'':'urn:lsid:ipni.org:names:123-1'];
$before=$store->all();$mediaBefore=$store->mediaOne($photo['id']);
$preview=$store->reconcileTaxonomy($plan,'fixture',false);
ggCheck($preview['merged']===1&&$preview['unresolved']===1&&$store->all()===$before,'Dry run has no writes');
$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_merge BEFORE UPDATE ON tbl_grassguide_media FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'");
$failed=false;try{$store->reconcileTaxonomy($plan,'fixture',true);}catch(Throwable $e){$failed=true;}
$store->executeExactPrepared('DROP TRIGGER grassguide_fail_merge');
ggCheck($failed&&$store->all()===$before&&$store->mediaOne($photo['id'])===$mediaBefore,'Failed merge rolls back all records');
$record=['id'=>md5('taxonomy-sighting'),'species_id'=>$old['id'],'list'=>'Trip','notes'=>'Original identification','observed_at'=>1700000000,'location'=>null];
$store->syncSightings('taxonomy-fixture',[$record]);
$store->reconcileTaxonomy($plan,'fixture',true);
$after=$store->all();$moved=$store->mediaOne($photo['id']);
ggCheck($moved['species_id']===$target['id']&&$moved['payload']===$mediaBefore['payload'],'Photo ownership changes without invalidating content rights');
ggCheck($service->one($target['id'])['representative_image']===$photo['id'],'Default photo retained');
ggCheck($service->one($old['id'])['similar']==='Keep this editorial record.'&&$service->one($old['id'])['_merged_into']===$target['id'],'Historical editorial record retained');
ggCheck($store->reconcileTaxonomy($plan,'fixture',true)['already_applied']===3&&$store->all()===$after,'Repeat is a no-op');
$result=$store->syncSightings('taxonomy-fixture',[$record]);
ggCheck($result['sightings']==[$record]&&$result['ids']==[$target['id']],'Immutable sighting replay preserved; checklist resolves legacy IDs');
$result=$store->syncChecklist('taxonomy-fixture',str_repeat('a',32),[['seq'=>1,'id'=>$old['id'],'checked'=>false]]);
ggCheck($result['ids']===[],'Old-client uncheck removes canonical species');
$result=$store->syncChecklist('taxonomy-fixture',str_repeat('a',32),[['seq'=>2,'id'=>$old['id'],'checked'=>true]]);
ggCheck($result['ids']==[$target['id']],'Old-client check uses canonical species');
ggReject(fn()=>$service->save($old['id'],$old),'conflict');
$feed=$rights->operatorExport();$lookup=array_column($feed['species'],null,'id');
ggCheck(!isset($lookup[$old['id']])&&$feed['species_aliases']->{$old['id']}===$target['id'],'Only canonical species exported with redirect');
ggCheck($lookup[$unknown['id']]['taxonomy_status']==='unresolved','Unresolved species remains visible with warning metadata');
$apis->responses=[['searchResults'=>['results'=>[['scientificName'=>'Taxonomicus old','rank'=>'species','author'=>'Fixture','taxonomicStatus'=>'Homotypic_Synonym','acceptedConceptID'=>'urn:lsid:ipni.org:names:123-1']]]],['taxonConcept'=>['taxonomicStatus'=>'Accepted','nameString'=>'Taxonomicus accepted']]];
$check=$apis->fetch('kew',$old);
ggCheck($check['taxonomy']['accepted_name']==='Taxonomicus accepted'&&$check['taxonomy']['status']==='unresolved'&&empty($check['fields']),'Worker reports synonym for review without guessing a rename');
echo "PASS taxonomy: dry-run, full rollback, atomic merge, preserved photo/rights payload/default, archived editorial history, idempotence, old-client checklists and immutable sightings, unresolved visibility and synonym detection.\n";
