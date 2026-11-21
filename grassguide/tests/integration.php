<?php
if (PHP_SAPI!=='cli') exit(64);
ob_start();
require __DIR__.'/bootstrap.php';
$schema=new ChisimbaDatabaseSchema($store);
foreach (glob(dirname(__DIR__).'/sql/*.sql') as $file) {
    $tableIndexes=[]; include $file;
    $schema->createTable($tablename,$fields,$options);
    foreach($tableIndexes as $name=>$index)$schema->createIndex($tablename,$name,$index);
}
$preview=$service->seedPreview(); ggCheck($preview['total']===179 && count($preview['missing'])===179,'Seed counts');
$user->admin=false;
foreach ([fn()=>$service->all(),fn()=>$service->import($preview['sha256']),fn()=>$service->export(),fn()=>$service->save(str_repeat('a',32),[]),fn()=>$service->saveMedia('x','y',[])] as $call) ggReject($call,'forbidden');
$user->admin=true; $user->logged=false; ggReject(fn()=>$service->all(),'forbidden'); $user->logged=true;
ggReject(fn()=>$service->import('bad'),'seed_changed');
ggCheck($service->import($preview['sha256'])===179,'Initial import');
ggCheck($service->import($preview['sha256'])===0,'Repeat import');
$row=$service->all()[0]; $row['diagnostic']="Leaf edge 'quoted' <script>alert(1)</script> é 🌿";
$saved=$service->save($row['id'],$row); ggCheck($saved['diagnostic']===$row['diagnostic'],'Exact Unicode content');
ggReject(fn()=>$service->save($row['id'],$row),'conflict');
ggCheck($service->import($preview['sha256'])===0 && $service->one($row['id'])['diagnostic']===$row['diagnostic'],'Import preserves edits');
$changed=$saved; $changed['genus']='Testus'; $changed['epithet']='fixture';
$changed=$service->save($changed['id'],$changed);
ggCheck(str_contains($changed['synonyms'],$row['scientific_name']) && $changed['taxonomy_review']==='pending','Rename preserves synonyms and resets review');
ggCheck($service->import($preview['sha256'])===0,'Renamed seed not duplicated');
$bad=$changed; $bad['ecology_review']='reviewed'; ggReject(fn()=>$service->save($bad['id'],$bad),'ecology_evidence');
$bad=$changed; $bad['heads']=['digitate']; ggReject(fn()=>$service->save($bad['id'],$bad),'trait_evidence');
$bad=$changed; $bad['taxonomy_checked']='2026-02-30'; ggReject(fn()=>$service->save($bad['id'],$bad),'invalid_date');
$bad=$changed; $bad['state']='reviewed'; ggReject(fn()=>$service->save($bad['id'],$bad),'review_incomplete');
$bad=$changed; $bad['heads']=[['digitate']]; ggReject(fn()=>$service->save($bad['id'],$bad),'invalid');
$complete=$changed; $complete['taxonomy_review']='reviewed'; $complete['taxonomy_checked']='2026-01-01'; $complete['powo_id']='urn:lsid:ipni.org:names:123456-1'; $complete['taxonomy_source']='Synthetic fixture only'; $complete['ecology_review']='reviewed'; $complete['ecology_source']='Synthetic fixture only'; $complete['heads']=['digitate','racemes_axis']; $complete['trait_source']='Synthetic fixture only'; $complete['state']='reviewed';
$complete=$service->save($complete['id'],$complete); ggCheck($complete['state']==='reviewed','Evidence-backed review');
$id=bin2hex(random_bytes(16));
$media=['revision'=>'0','kind'=>'image','role'=>'inflorescence','state'=>'active','source_url'=>'https://example.org/observation','image_url'=>'https://example.org/image.jpg','caption'=>'Seed head','alt_text'=>'Two branching seed heads','creator'=>'Fixture photographer','licence'=>'CC-BY-NC-4.0'];
$m=$service->saveMedia($complete['id'],$id,$media); ggCheck($m['creator']==='Fixture photographer','Media credits persisted');
ggReject(fn()=>$service->saveMedia($complete['id'],$id,$media),'conflict');
$other=$service->all()[0]; if($other['id']===$complete['id'])$other=$service->all()[1];
ggReject(fn()=>$service->saveMedia($other['id'],$id,$m),'conflict');
$m['state']='archived'; $service->saveMedia($complete['id'],$id,$m); ggCheck($service->mediaFor($complete['id'])[0]['state']==='archived','Archive retains media');
foreach (['javascript:alert(1)','http://example.org/a','https://user:secret@example.org/a'] as $url) { $bad=$media; $bad['source_url']=$url; ggReject(fn()=>grassguideinput::media($bad),'invalid_url'); }
$bad=$media; $bad['licence']='permission'; ggReject(fn()=>grassguideinput::media($bad),'rights_required');
$bad=$media; $bad['kind']='youtube'; $bad['youtube_id']='bad'; ggReject(fn()=>grassguideinput::media($bad),'invalid_video');
$video=$media; $video['kind']='youtube'; $video['youtube_id']='abcdefghijk'; $video['licence']='YouTube'; $video['image_url']='';
ggCheck($service->saveMedia($complete['id'],bin2hex(random_bytes(16)),$video)['youtube_id']==='abcdefghijk','Video reference');
// Failure after the row update must roll back the row as well as its audit.
$before=$service->one($complete['id']);
$store->executeExactPrepared("CREATE TRIGGER grassguide_fail_audit BEFORE INSERT ON tbl_grassguide_audit FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture'");
$failed=false; try {$input=$before; $input['description']='Must roll back'; $service->save($input['id'],$input);} catch(Throwable $e){$failed=true;}
$store->executeExactPrepared('DROP TRIGGER grassguide_fail_audit');
ggCheck($failed && $service->one($complete['id'])===$before,'Audit failure rolls back content');
ggCheck(count($service->export()['species'])===179,'Editorial export count');
ggCheck(count($service->history($complete['id']))>=5,'Audit history');
// Controller origin/method checks exercise the real CSRF service.
$_GET=['action'=>'import']; $_POST=[]; $_SERVER['REQUEST_METHOD']='GET'; http_response_code(200);
$c=new grassguide(); $c->dispatch(); ggCheck(http_response_code()===405 && count($service->all())===179,'GET mutation denied');
$_POST=['csrf_token'=>'bad','seed_hash'=>$preview['sha256']]; $_SERVER['REQUEST_METHOD']='POST'; http_response_code(200);
$c=new grassguide(); $c->dispatch(); ggCheck(http_response_code()===403,'Bad CSRF denied');
$_GET=['action'=>'edit','id'=>$complete['id']]; $_POST=[]; $_SERVER['REQUEST_METHOD']='GET'; http_response_code(200);
$c=new grassguide(); $template=$c->dispatch(); ob_start();$c->render($template);$html=ob_get_clean();
ggCheck(!str_contains($html,'<script>alert(1)</script>') && str_contains($html,'&lt;script&gt;alert(1)&lt;/script&gt;'),'Output escaped');
ggCheck(!preg_match('/mod_grassguide_[a-z_]+/',$html),'All visible editor strings registered');
$user->admin=false; http_response_code(200); $c=new grassguide(); ggCheck($c->dispatch()==='denied_tpl.php' && http_response_code()===403,'Controller denies revoked admin');
ggCheck($GLOBALS['objects']['helpcontent']->getTopic('administration')===null,'Help permission enforced');
$user->admin=true;
echo "PASS: 179-species import/replay, permissions, exact Unicode, stale saves, rename, review evidence, media ownership/licences/archive, transaction rollback, export, HTTP method/CSRF, escaping and Help.\n";

require __DIR__.'/enrichment.php';

require __DIR__."/rights.php";

ob_end_flush();
