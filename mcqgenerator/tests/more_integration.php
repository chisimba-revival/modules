<?php
if(PHP_SAPI!=='cli'||getenv('QUESTIONWORKSHOP_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$engine=new engine();
$store=$engine->getObject('workshopstore','mcqgenerator');$id=null;$exams=$engine->getObject('examstore','mcqgenerator');$exam=$exams->createExam('local-append-fixture','Append fixture',$engine->getObject('examservice','mcqgenerator')->emptyContent());
try{
 $q=['stem'=>'Original?','options'=>['A','B','C','D'],'correctIndex'=>1,'sourceBasis'=>'Original evidence.','included'=>false];
 $id=$store->createSet('local-append-fixture','Disposable append test',str_repeat('Source. ',30),[],1,$exam);$store->finish($store->one($id),[$q]);$old=$store->one($id);
 if(!$store->claimMore($old,['baseQuestions'=>[$q]])||$store->claimMore($old,['baseQuestions'=>[$q]]))throw new RuntimeException('Claim not exclusive');
 try{$store->saveSet($old,'Stale save',[$q],true);throw new RuntimeException('Stale save accepted');}catch(DomainException $e){if($e->getMessage()!=='changed')throw $e;}
 $active=$store->one($id);try{$store->saveSet($active,'In-flight save',[$q],true);throw new RuntimeException('In-flight save accepted');}catch(DomainException $e){if($e->getMessage()!=='changed')throw $e;}
 $new=$q;$new['stem']='Additional?';$store->finish($active,[$q,$new],[],2,false);$row=$store->one($id);
 if(json_decode($row['questions_json'],true)!==[$q,$new]||(int)$row['question_count']!==2||(int)$row['reviewed']!==0||$row['state']!=='generated')throw new RuntimeException('Append not persisted');
 echo "PASS real DB append claim, duplicate request blocking, stale/in-flight edit rejection and final state.\n";
}finally{if($id){$row=$store->one($id);$store->removeOwned($id,$row['ownerid'],(int)$row['version']);}$exams->removeOwned($exams->one($exam));}
