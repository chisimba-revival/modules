<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=null){return $GLOBALS['services'][$name];}}
foreach(['workshopplan','workshopservice','workshopsource'] as $name)require dirname(__DIR__).'/classes/'.$name.'_class_inc.php';
function verify($yes,$label){if(!$yes)throw new RuntimeException($label);}
$planner=new workshopplan();$source=str_repeat("Grasses flower. é 😀\n\n",200);$capacity=['sourceBytes'=>1000,'outputTokens'=>4000,'provider'=>'fixture','model'=>'fixture'];
$plan=$planner->build($source,10,$capacity);
verify(implode('',$plan['parts'])===$source,'Lossless sections');
verify(array_sum($plan['counts'])===10,'Total allocation');
foreach($plan['parts'] as $p)verify(strlen($p)<=1000&&mb_check_encoding($p,'UTF-8'),'UTF-8 section bound');
$GLOBALS['services']['aicapacity']=new class($capacity){public function __construct(public $c){}public function forTextGeneration(){return $this->c;}};
$GLOBALS['services']['workshopplan']=$planner;
$store=new class {
 public $row;public function claim($row){if($this->row['state']!=='ready')return false;$this->row['state']='generating';return true;}
 public function saveJob($row,$job,$state='generating'){$this->row['generation_json']=json_encode($job);$this->row['state']=$state;}
 public function claimPart($row){if($this->row['state']!=='generating')return false;$this->row['state']='processing';return true;}
 public function finish($row,$questions,$issues=[]){$this->row['questions_json']=json_encode($questions);$this->row['validation_json']=json_encode($issues);$this->row['state']='generated';}
};$GLOBALS['services']['workshopstore']=$store;
$provider=new class {public $calls=0,$failAt=0;public function generate($text,$count,$output){$this->calls++;if($this->calls===$this->failAt)return ['ok'=>false];$q=['stem'=>'Which plant?','options'=>['Grass','Rock','Cloud','Sand'],'correctIndex'=>0,'sourceBasis'=>'Grasses flower.'];return ['ok'=>true,'questions'=>array_fill(0,$count,$q)];}};
$GLOBALS['services']['mcqaigenerator']=$provider;
$service=new class extends workshopservice {public function read($id){return $GLOBALS['services']['workshopstore']->row;}};
$reset=function()use($store,$source,$provider){$store->row=['id'=>'fixture','source_text'=>$source,'question_count'=>10,'state'=>'ready'];$provider->calls=0;};
$reset();$service->begin('fixture');verify($provider->calls===0,'Planning makes no paid request');
while($store->row['state']==='generating')$service->part('fixture');
verify($provider->calls===count($plan['parts']),'Exactly one request per section');
verify(count(json_decode($store->row['questions_json'],true))===10,'Combined questions');
try{$service->part('fixture');throw new RuntimeException('Repeated completed job');}catch(DomainException $e){verify($e->getMessage()==='generation_busy','Duplicate blocked');}
$reset();$provider->failAt=2;$service->begin('fixture');$service->part('fixture');$service->part('fixture');
verify($store->row['state']==='generated'&&count(json_decode($store->row['questions_json'],true))>0,'Partial results saved');
verify(in_array('partial',array_column(json_decode($store->row['validation_json'],true),'code')),'Partial coverage disclosed');
echo "PASS lossless Unicode sections, allocation, no-call planning, checkpoints, duplicate prevention and partial failure.\n";
