<?php
/** Bounded CLI worker. Run separate provider processes; each owns a database lock. */
if(PHP_SAPI!=='cli')exit(64);
chdir(dirname(__DIR__,3));
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require_once 'classes/core/engine_class_inc.php';
try {
    $engine=new engine();$worker=$engine->getObject('grassguideworker','grassguide');
    $provider=$argv[1] ?? '';
    if($provider==='queue') { $engine->getObject('grassguideproviders','grassguide');echo json_encode(['queued'=>$engine->getObject('grassguidestore','grassguide')->queueEnrichment()]).PHP_EOL;exit(0); }
$limit=filter_var($argv[2] ?? 20,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]]);
    if($limit===false)throw new InvalidArgumentException('Batch size must be 1–1000');
    for($i=0;$i<$limit;$i++) { $result=$worker->runOne($provider);echo json_encode($result,JSON_UNESCAPED_SLASHES).PHP_EOL;if(!$result['selected'])break; }
} catch(Throwable $e) { fwrite(STDERR,'Grass enrichment worker: '.$e->getMessage().PHP_EOL);exit(1); }
