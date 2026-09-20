<?php
/** Short-answer contracts using synthetic data and a fake shared AI boundary. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=null){return $GLOBALS['services'][$name];}}
foreach(['workshopservice','shortanswergenerator','workshopexport','examservice','workshoppolicy','workshoprenderer'] as $class)require dirname(__DIR__).'/classes/'.$class.'_class_inc.php';
function check($ok,$label){if(!$ok)throw new RuntimeException($label);}
function rejects($fn,$code){try{$fn();}catch(DomainException $e){check($e->getMessage()===$code,$code);return;}throw new RuntimeException('Expected '.$code);}
$service=new workshopservice();$exam=new examservice();$export=new workshopexport();
$GLOBALS['services']=['workshopservice'=>$service,'workshopexport'=>$export,'workshoppolicy'=>new workshoppolicy(),
 'workshoprenderer'=>new class{function text($key){return $key;}},'user'=>new class{function userId(){return 'owner';}function isLoggedIn(){return true;}function isAdmin(){return true;}}];
$q=['type'=>'short_answer','stem'=>'Give two benefits of shade.','modelAnswer'=>'Shade reduces heat and provides shelter.','markingPoints'=>"Reduced heat: 1 mark\nShelter: 1 mark",'marks'=>2,'sourceBasis'=>'Shade reduces heat and provides shelter.'];
check($service->validate([$q],1)===[ $q ],'short-answer validation');
foreach(['modelAnswer','markingPoints','sourceBasis'] as $field){$bad=$q;$bad[$field]='';rejects(fn()=>$service->validate([$bad],1),'questions_invalid');}
$bad=$q;$bad['modelAnswer']=str_repeat('word ',121);rejects(fn()=>$service->validate([$bad],1),'questions_invalid');
foreach([0,11,'2.5',[]] as $marks){$bad=$q;$bad['marks']=$marks;rejects(fn()=>$service->validate([$bad],1),'questions_invalid');}
$bad=$q;$bad['type']='unknown';rejects(fn()=>$service->validate([$bad],1),'questions_invalid');
$input=$q;$input['included']='1';unset($input['type']);check($service->review([$input],1,'short_answer')[0]['modelAnswer']===$q['modelAnswer'],'persisted type drives review');
$bad=$input;$bad['modelAnswer']='';unset($bad['included']);check(!$service->review([$bad],1,'short_answer')[0]['included'],'excluded incomplete candidate retained');
$candidates=$service->candidates([$q],[['code'=>'quote','question'=>1]],'short_answer');check(!$candidates[0]['included']&&$candidates[0]['type']==='short_answer','flagged answer excluded');
$set=['id'=>'short','examid'=>'exam','ownerid'=>'owner','title'=>'Short set','version'=>1,'reviewed'=>1,'questions_json'=>json_encode([$q])];
$paper=$export->text($set);$key=$export->text($set,true);check(!str_contains($paper,$q['modelAnswer'])&&!str_contains($paper,'Reduced heat: 1 mark'),'set paper excludes marking material');check(str_contains($key,$q['modelAnswer'])&&str_contains($key,'Reduced heat: 1 mark'),'set key includes model and criteria');
$mcq=['stem'=>'Which reduces heat?','options'=>['Shade','Sun','Fire','Steam'],'correctIndex'=>0,'sourceBasis'=>'Shade reduces heat.'];
$row=['id'=>'exam','ownerid'=>'owner','title'=>'Mixed paper','version'=>1,'content_json'=>json_encode($exam->emptyContent())];
$content=$exam->add($row,$set,['0']);check($content['questions'][0]['marks']===2,'suggested marks used');$row['content_json']=json_encode($content);
$mcqSet=$set;$mcqSet['id']='mcq';$mcqSet['questions_json']=json_encode([$mcq]);$content=$exam->add($row,$mcqSet,['0']);$before=$content['questions'][0];$content=$exam->mixAnswers($content);check($before===$content['questions'][0],'MCQ mixing leaves short answers untouched');$row['content_json']=json_encode($content);
$paper=implode("\n",$exam->lines($row));$key=implode("\n",$exam->lines($row,true));check(!str_contains($paper,$q['modelAnswer'])&&str_contains($key,$q['modelAnswer']),'mixed paper answer separation');check(str_contains($paper,'exam_total: 3'),'mixed marks total');
$path=tempnam('/tmp','short-odt');try{file_put_contents($path,$exam->odt($row));$zip=new ZipArchive();check($zip->open($path)===true,'mixed ODT opens');$xml=$zip->getFromName('content.xml');check(!str_contains($xml,$q['modelAnswer']),'ODT paper answer separation');$zip->close();file_put_contents($path,$exam->odt($row,true));$zip->open($path);check(str_contains($zip->getFromName('content.xml'),'<text:line-break/>'),'criteria line breaks preserved');$zip->close();}finally{unlink($path);}
class FakeShortAI {public $request;public $result;function isAvailable(){return true;}function execute($request){$this->request=$request;return $this->result;}}
$ai=new FakeShortAI();$GLOBALS['services']['aiservice']=$ai;$generator=new shortanswergenerator();$source=str_repeat($q['sourceBasis'].' ',4);$raw=$q;unset($raw['type']);$ai->result=['ok'=>true,'data'=>['questions'=>[$raw]]];
check($generator->generate($source,1)['ok'],'shared AI validated response');check($ai->request['consumer']==='mcqgenerator'&&str_contains($ai->request['instructions'],'two benefits and two drawbacks')&&str_contains($ai->request['instructions'],'two to three sentences'),'consumer and question design instructions');
check(!isset($ai->request['schema']['properties']['questions']['items']['properties']['options']),'no MCQ options requested');
$ai->result['data']['questions'][0]['sourceBasis']='Invented source';$result=$generator->generate($source,1);check(!$result['ok']&&$result['issues'][0]['code']==='quote'&&isset($result['candidates']),'ungrounded candidates flagged');
$ai->result=['ok'=>false,'error'=>'openai_timeout'];check($generator->generate($source,1)['error']==='openai_timeout','uncertain failure propagated without retry');
echo "PASS short-answer validation, review, exclusions, mixed exams/marks, answer-safe text/ODT, shared AI request contract, grounding and timeout handling.\n";
