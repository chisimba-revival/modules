<?php
/** Append lifecycle without provider calls or user data. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($name,$module=null){return $GLOBALS['services'][$name];}}
foreach(['workshopservice','workshopplan'] as $name)require dirname(__DIR__).'/classes/'.$name.'_class_inc.php';
function verify($yes,$label){if(!$yes)throw new RuntimeException($label);}
$base=[['stem'=>'Original human edited question?','options'=>['A','B','C','D'],'correctIndex'=>2,'sourceBasis'=>'Saved evidence','included'=>false]];
$store=new class {
 public $row,$requested,$reviewed;
 function claimMore($row,$job){if($this->row['version']!==$row['version']||$this->row['state']!=='generated')return false;$this->row['state']='generating';$this->row['version']++;$this->row['generation_json']=json_encode($job);return true;}
 function claimPart($row){if($this->row['state']!=='generating')return false;$this->row['state']='processing';return true;}
 function saveJob($row,$job,$state='generating'){$this->row['generation_json']=json_encode($job);$this->row['state']=$state;}
 function finish($row,$q,$issues=[],$count=null,$reviewed=false){$this->row['questions_json']=json_encode($q);$this->row['validation_json']=json_encode($issues);$this->row['state']='generated';$this->requested=$count;$this->reviewed=$reviewed;}
};
$provider=new class {public $fail=false,$seen=[],$calls=0;function generate($source,$count,$output,$stems=[]){$this->calls++;$this->seen=$stems;if($this->fail)return ['ok'=>false,'error'=>'openai_timeout'];$base=json_decode($GLOBALS['services']['workshopstore']->row['questions_json'],true)[0];$new=$base;$new['stem']='A new question?';return ['ok'=>true,'questions'=>[$base,$new]];}};
$GLOBALS['services']=['workshopstore'=>$store,'mcqaigenerator'=>$provider,'workshopplan'=>new workshopplan(),'aicapacity'=>new class{function forTextGeneration(){return ['sourceBytes'=>1000000,'outputTokens'=>32000,'provider'=>'fixture','model'=>'fixture'];}}];
$s=new class extends workshopservice{function read($id){return $GLOBALS['services']['workshopstore']->row;}};
$reset=function()use($store,$base){$store->row=['id'=>'fixture','version'=>3,'state'=>'generated','reviewed'=>1,'question_count'=>1,'source_text'=>str_repeat('Synthetic source. ',20),'questions_json'=>json_encode($base),'validation_json'=>json_encode([['code'=>'quote','question'=>1]])];};
$reset();$s->more('fixture',2,'3');verify($provider->calls===0,'No request until processing');
try{$s->more('fixture',2,'3');throw new RuntimeException('Duplicate accepted');}catch(DomainException $e){verify($e->getMessage()==='changed','Stale double click blocked');}
$s->part('fixture');$result=json_decode($store->row['questions_json'],true);verify($result[0]===$base[0],'Original question including edits and exclusion preserved');verify(count($result)===2,'Duplicate stem omitted');verify($provider->seen===[$base[0]['stem']],'Existing stems sent as avoidance context');verify($store->requested===3,'Requested total updated');verify(!$store->reviewed,'New questions require review');
$issues=json_decode($store->row['validation_json'],true);verify($issues[0]['question']===1,'Old report reference retained');verify(end($issues)['code']==='additional_shortfall','Shortfall explained');
$reset();$provider->fail=true;$s->more('fixture',2,'3');$s->part('fixture');verify(json_decode($store->row['questions_json'],true)===$base,'Timeout preserves all originals');verify($store->reviewed,'Failed append preserves prior review');verify($store->row['state']==='generated','Can retry after failure');
$reset();try{$s->more('fixture',30,'3');throw new RuntimeException('Limit bypass');}catch(DomainException $e){verify($e->getMessage()==='more_limit','Total bounded');}
echo "PASS append snapshots, duplicate avoidance, revision guard, review status, failure recovery and total limit.\n";
