<?php
/** Behavioural checks without paid providers or private data. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=null){return $GLOBALS['services'][$name];}}
foreach(['workshopservice','workshopsource','workshopexport','workshoppolicy'] as $name)require dirname(__DIR__).'/classes/'.$name.'_class_inc.php';
require dirname(__DIR__,2).'/ingestservice/classes/odtingestparser_class_inc.php';
$checks=0;
function check($ok,$label){global $checks;++$checks;if(!$ok)throw new RuntimeException($label);}
function rejects($fn,$label){try{$fn();}catch(DomainException $e){check(true,$label);return;}check(false,$label);}
$GLOBALS['services']=['workshoprenderer'=>new class {public function text($key){return $key;}},'odtingestparser'=>new odtingestparser(),
'user'=>new class {public $admin=false,$teacher=true,$logged=true,$id='owner';public function isAdmin(){return $this->admin;}public function isLecturer(){return $this->teacher;}public function isLoggedIn(){return $this->logged;}public function userId(){return $this->id;}}];
$service=new workshopservice();$source=new workshopsource();$export=new workshopexport();$policy=new workshoppolicy();
$q=['stem'=>'Which plant has flowers? <example>','options'=>['Tree & shrub','Rock','Cloud','Sand'],'correctIndex'=>0,'sourceBasis'=>'The tree has flowers.'];
$questions=array_fill(0,5,$q);
check(count($service->validate($questions))===5,'valid set');
$bad=$questions;$bad[0]['correctIndex']=8;rejects(fn()=>$service->validate($bad),'bad correct index');
$bad=$questions;$bad[0]['options'][1]=$bad[0]['options'][0];rejects(fn()=>$service->validate($bad),'duplicate options');
rejects(fn()=>$source->validate('short'),'short source');rejects(fn()=>$source->validate(str_repeat('x',40001)),'oversize source');rejects(fn()=>$source->validate(str_repeat('x',100)."\0"),'binary source');
check($policy->owner(['ownerid'=>'owner']),'owner access');check(!$policy->owner(['ownerid'=>'someone']),'other owner denied');$GLOBALS['services']['user']->teacher=false;check(!$policy->allowed(),'student denied');$GLOBALS['services']['user']->admin=true;check(!$policy->owner(['ownerid'=>'someone']),'admin cannot take private owner set');
$set=['title'=>'Field questions 🌿','questions_json'=>json_encode($questions),'reviewed'=>0];
$paper=$export->text($set);$key=$export->text($set,true);
check(!str_contains($paper,$q['sourceBasis']),'paper excludes supporting answer excerpt');check(str_contains($key,$q['sourceBasis']),'key includes supporting excerpt');check(str_contains($paper,'draft_notice'),'draft labelled');
$path=tempnam(sys_get_temp_dir(),'workshop-test-');
try{file_put_contents($path,$export->odt($set));$zip=new ZipArchive();check($zip->open($path)===true,'ODT opens');check($zip->getFromName('mimetype')==='application/vnd.oasis.opendocument.text','ODT mimetype');check($zip->statIndex(0)['comp_method']===0,'ODT mimetype uncompressed');$xml=$zip->getFromName('content.xml');$dom=new DOMDocument();check($dom->loadXML($xml,LIBXML_NONET),'ODT XML well formed');check(str_contains($xml,'&lt;example&gt;'),'XML escaped');$zip->close();$text=$source->file($path,'questions.odt');check(str_contains($text,'Field questions 🌿'),'ODT round trip through shared parser');check(!str_contains($text,$q['sourceBasis']),'ODT paper excludes key');}finally{unlink($path);}
echo 'PASS '.$checks." source, ownership, validation and export checks.\n";
require dirname(__DIR__,2).'/mcqtests/classes/mcqaigenerator_class_inc.php';
$generator=new mcqaigenerator();
$mock=new class {
 public $wrong=false;
 public function isAvailable(){return true;}
 public function execute($request){$n=$request['schema']['properties']['questions']['minItems'];return ['ok'=>true,'data'=>['questions'=>array_fill(0,$this->wrong?2:$n,['stem'=>'Which plant has flowers?','options'=>['Tree','Rock','Cloud','Sand'],'correctIndex'=>0,'sourceBasis'=>'The tree has flowers.'])]];}
};
$prop=new ReflectionProperty(mcqaigenerator::class,'aiService');$prop->setValue($generator,$mock);
$sourceText=str_repeat('The tree has flowers. ',8);
foreach([1,3,5,30] as $count){$result=$generator->generate($sourceText,$count);check($result['ok']&&count($result['questions'])===$count,'requested count '.$count);}
check(count($generator->generate($sourceText)['questions'])===5,'existing default unchanged');
check(!$generator->generate($sourceText,0)['ok']&&!$generator->generate($sourceText,31)['ok'],'count bounds');
$mock->wrong=true;check(!$generator->generate($sourceText,3)['ok'],'wrong provider count rejected');
echo "PASS configurable shared generator counts, limits and unchanged default.\n";
class GenerationFailureFixture extends workshopservice {
 public function read($id){return ['source_text'=>'Synthetic fixture source','question_count'=>3];}
}
$GLOBALS['services']['workshopstore']=new class {public $failed=false;public function claim($row){return true;}public function failed($row){$this->failed=true;}};
$GLOBALS['services']['mcqaigenerator']=new class {public $code;public function generate($source,$count){return ['ok'=>false,'error'=>$this->code];}};
foreach(['grounding_validation_failed'=>'generation_grounding','ai_unavailable'=>'generation_unavailable','openai_transport_error'=>'generation_provider'] as $code=>$expected){
 $GLOBALS['services']['mcqaigenerator']->code=$code;
 try{(new GenerationFailureFixture())->generate('fixture');throw new RuntimeException('Failure swallowed');}
 catch(DomainException $e){check($e->getMessage()===$expected,'actionable generation error');}
}
echo "PASS specific generation failures preserved for user feedback.\n";
