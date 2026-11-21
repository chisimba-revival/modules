<?php
/** Included by the real-PDO integration fixture. No network. */
$apis=new class extends grassguideproviders {
    public array $responses=[];
    protected function request(string $provider,string $path,array $query=[]): array { return array_shift($this->responses) ?? []; }
};
$GLOBALS['objects']['grassguideproviders']=$apis;
$GLOBALS['objects']['grassguideworker']=$worker=new grassguideworker();
$user->admin=false;ggReject(fn()=>$service->queueEnrichment(),'forbidden');$user->admin=true;
ggCheck($service->queueEnrichment()===895,'All species/provider jobs queued');
ggCheck($service->queueEnrichment()===0,'Enqueue replay deduplicates');
$job=$store->nextEnrichment('wikipedia');$species=grassguideservice::unpack($store->one($job['species_id']));
$name=$species['scientific_name'];
$apis->responses=[['query'=>['pages'=>['1'=>['pageid'=>1,'title'=>$name,'extract'=>$name." is a grass.\n\n== Description ==\nThe inflorescence is an open panicle.\n\n== Habitat ==\nGrassland.",'revisions'=>[['revid'=>42]]]]]]];
$result=$apis->fetch('wikipedia',$species);
ggCheck($result['fields']['heads']===['open_panicle'] && $result['fields']['habitat']==='Grassland.','Explicit sourced sections and traits');
$store->applyEnrichment($job,$result);$first=$service->one($species['id']);
ggCheck($first['description']!=='' && isset($first['_field_sources']['description']),'Fetched data and attribution persisted atomically');
$priority=$result;$priority['fields']=['description'=>'Botanical source description'];$priority['sources']=['description'=>array_merge($result['sources']['description'],['provider'=>'grassbase'])];
$grassJob=$job;$grassJob['provider']='grassbase';$store->applyEnrichment($grassJob,$priority);$first=$service->one($species['id']);
ggCheck($first['description']==='Botanical source description','Botanical source supersedes unchanged general-source text');
$manual=$first;$manual['description']='My correction';$manual['diagnostic']='';$service->save($manual['id'],$manual);
$store->applyEnrichment($job,$result);$store->applyEnrichment($grassJob,$priority);$next=$service->one($manual['id']);
ggCheck($next['description']==='My correction' && $next['diagnostic']==='' && $next['_manual_fields']['diagnostic'],'Human corrections and intentional blank protected');
ggCheck(array_values(array_filter($store->enrichmentJobs($manual['id']),fn($j)=>$j['provider']==='wikipedia'))[0]['status']==='done','Result cached');
$apis->responses=[['query'=>['pages'=>['1'=>['pageid'=>1,'title'=>$species['genus'],'extract'=>'Genus overview']]]]];
ggCheck(!$apis->fetch('wikipedia',$species)['matched'],'Genus redirect rejected');
$apis->responses=[['results'=>[['id'=>1,'name'=>$name,'rank'=>'species','is_active'=>true,'ancestor_ids'=>[47434]]]],['results'=>[['id'=>1,'name'=>$name,'is_active'=>true,'taxon_photos'=>[
 ['photo'=>['id'=>11,'license_code'=>'cc-by-nc','attribution_name'=>'Fixture photographer','attribution'=>'CC BY-NC','large_url'=>'https://example.org/photo.jpg']],
 ['photo'=>['id'=>12,'license_code'=>null,'attribution_name'=>'Reserved','large_url'=>'https://example.org/reserved.jpg']]
]]]]];
$photos=$apis->fetch('inaturalist',$species);ggCheck(count($photos['media'])===1 && $photos['media'][0]['role']==='other','Licensed photograph only; no invented diagnostic role');
$photoJob=$store->nextEnrichment('inaturalist');$photoJob['species_id']=$species['id']; // Isolate merge assertions on one species.
$store->applyEnrichment($photoJob,$photos);$store->applyEnrichment($photoJob,$photos);
ggCheck(count($service->mediaFor($species['id']))===1,'Photo replay is idempotent');
$store->failEnrichment($job,429,7200);$jobs=$store->enrichmentJobs($species['id']);
$retry=array_values(array_filter($jobs,fn($j)=>$j['provider']==='wikipedia'))[0];
ggCheck($retry['status']==='retry' && strtotime($retry['due_at'].' UTC')>=time()+7198,'Retry-After respected');
ggCheck($store->nextEnrichment('wikipedia')===null,'Provider cooldown covers remaining queue');
$job=$store->nextEnrichment('kew');$row=grassguideservice::unpack($store->one($job['species_id']));
$before=$row;$r=['name'=>$row['scientific_name'],'matched'=>true,'fields'=>['habitat'=>'Rollback fixture'],'sources'=>['habitat'=>['provider'=>'kew']],'media'=>[]];
$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_enrichment BEFORE INSERT ON tbl_grassguide_audit FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'");
$failed=false;try{$store->applyEnrichment($job,$r);}catch(Throwable $e){$failed=true;}
$store->executeExactPrepared('DROP TRIGGER grassguide_fail_enrichment');
ggCheck($failed && $service->one($row['id'])===$before,'Failed atomic enrichment rolls back species');
$store->executeExactPrepared('UPDATE tbl_grassguide_jobs SET status=\'running\',due_at=? WHERE id=?',[gmdate('Y-m-d H:i:s'),$job['id']]);
ggCheck($store->nextEnrichment('kew')['id']===$job['id'],'Interrupted job can resume');
echo "PASS enrichment: durable deduplicated jobs, exact matches, sourced sections, licensed images, replay, manual overrides, shared cooldown and rollback.\n";

$grass=$apis->fetch('grassbase',['scientific_name'=>'Themeda triandra']);
ggCheck($grass['matched'] && $grass['fields']['awns']==='present' && in_array('compound',$grass['fields']['heads'],true),'GrassBase structured traits');
ggCheck(str_contains($grass['fields']['description'],'FLORETS') && count($grass['sources'])===count($grass['fields']),'Botanical text and provenance');
ggCheck(!$apis->fetch('grassbase',['scientific_name'=>'Testus fixture'])['matched'],'No guessed GrassBase match');
$leersia=$apis->fetch('grassbase',['scientific_name'=>'Leersia hexandra']);
ggCheck($leersia['fields']['awns']==='absent' && in_array('open_panicle',$leersia['fields']['heads'],true),'Implicit awn state and open panicle mapped');
echo "PASS GrassBase pinned cache, sourced botanical descriptions, explicit trait mapping and unknown names.\n";
