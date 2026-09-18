<?php
// Disposable local fixture only; never expose as a web endpoint.
if(PHP_SAPI!=='cli' || getenv('QUESTIONWORKSHOP_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$e=new engine();$f=json_decode(file_get_contents('/tmp/workshop-fixture.json'),true);$store=$e->getObject('workshopstore','questionworkshop');$row=$store->one($f['set']);
if(!$row||$row['ownerid']!==$f['ids']['teacher'])throw new RuntimeException('Fixture owner');
if(!$store->claim($row)||$store->claim($row))throw new RuntimeException('Generation claim');
$questions=[];for($i=0;$i<3;$i++)$questions[]=['stem'=>'QA question '.($i+1).' 🌿','options'=>['Tree','Rock','Cloud','Sand'],'correctIndex'=>0,'sourceBasis'=>'The tree has green leaves'];
$store->finish($row,$questions);echo "PASS persisted synthetic questions and duplicate generation claim rejection.\n";
