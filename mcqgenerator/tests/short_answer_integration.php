<?php
/** Local real-store lifecycle with a synthetic AI boundary; never calls a provider. */
if(PHP_SAPI!=='cli'||getenv('QUESTIONWORKSHOP_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$engine=new engine();
$store=$engine->getObject('workshopstore','mcqgenerator');$exams=$engine->getObject('examstore','mcqgenerator');$engine->loadClass('workshopservice','mcqgenerator');$engine->loadClass('shortanswergenerator','mcqgenerator');
class SyntheticShortAI {public $number=0;public $fail=false;function isAvailable(){return true;}function execute($request){if($this->fail)return ['ok'=>false,'error'=>'openai_timeout'];$this->number++;return ['ok'=>true,'data'=>['questions'=>[['stem'=>'Explain benefit '.$this->number.' of shade.','modelAnswer'=>'Shade reduces heat and provides shelter.','markingPoints'=>"Reduced heat: 1 mark\nShelter: 1 mark",'marks'=>2,'sourceBasis'=>'Shade reduces heat and provides shelter.']]]];}}
class SyntheticShortGenerator extends shortanswergenerator {public $fake;public function getObject($name,$module=''){return $name==='aiservice'?$this->fake:parent::getObject($name,$module);}}
class ShortWorkflowFixture extends workshopservice {public $fake;public $owner;public function getObject($name,$module=''){if($name==='shortanswergenerator')return $this->fake;if($name==='aicapacity')return new class{function forTextGeneration(){return ['sourceBytes'=>100000,'outputTokens'=>5000];}};return parent::getObject($name,$module);}public function read($id){$row=$this->getObject('workshopstore')->one($id);if(!$row||$row['ownerid']!==$this->owner)throw new DomainException('not_found');return $row;}}
$owner='short-fixture-'.bin2hex(random_bytes(4));$exam=$exams->createExam($owner,'Short lifecycle fixture',$engine->getObject('examservice','mcqgenerator')->emptyContent());$ids=[];
try{
 $source=trim(str_repeat('Shade reduces heat and provides shelter. ',6));
 $original=$ids[]=$store->createSet($owner,'Original MCQ source',$source,[],1,$exam);$before=$store->one($original);
 $id=$ids[]=$store->createSet($owner,'Short answers',$before['source_text'],[],1,$exam,'short_answer');
 $generator=new SyntheticShortGenerator($engine,'mcqgenerator');$generator->fake=new SyntheticShortAI();$service=new ShortWorkflowFixture($engine,'mcqgenerator');$service->owner=$owner;$service->fake=$generator;
 $service->begin($id,1);$service->part($id);$row=$store->one($id);$q=json_decode($row['questions_json'],true);
 if($row['state']!=='generated'||$q[0]['type']!=='short_answer'||$q[0]['marks']!==2)throw new RuntimeException('Typed generation not saved');
 $q[0]['modelAnswer']='Shade helps by reducing heat and providing shelter.';$q[0]['included']='1';$review=$service->review($q,1,'short_answer');$store->saveSet($row,'Edited short answers',$review,true);
 $row=$store->one($id);$service->more($id,1,(string)$row['version']);$service->part($id);$row=$store->one($id);$saved=json_decode($row['questions_json'],true);if(count($saved)!==2||$saved[0]['modelAnswer']!==$q[0]['modelAnswer'])throw new RuntimeException('Append lost edited model');
 $generator->fake->fail=true;$service->more($id,1,(string)$row['version']);$service->part($id);$after=$store->one($id);if(json_decode($after['questions_json'],true)!==$saved)throw new RuntimeException('Timeout lost questions');
 if($store->one($original)!==$before)throw new RuntimeException('Original MCQ source changed');
 try{$service->importCourse($id,'root');throw new RuntimeException('Short answer MCQ import permitted');}catch(DomainException $e){if($e->getMessage()!=='short_import_unavailable')throw $e;}
 echo "PASS real DB source reuse, typed generation, edited model save/reload, append, timeout preservation, original MCQ preservation and MCQ-import refusal.\n";
}finally{foreach($ids as $id){$row=$store->one($id);if($row)$store->removeOwned($id,$owner,(int)$row['version']);}$exams->removeOwned($exams->one($exam));}
