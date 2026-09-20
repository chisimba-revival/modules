<?php
/** Exam contracts: no provider, no private user data. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=null){return $GLOBALS['services'][$name];}}
foreach(['examservice','workshopservice','workshopexport','workshoppolicy'] as $name)require dirname(__DIR__).'/classes/'.$name.'_class_inc.php';
$checks=0;function check($ok,$label){global $checks;++$checks;if(!$ok)throw new RuntimeException($label);}
function rejects($fn,$code){try{$fn();}catch(DomainException $e){check($e->getMessage()===$code,$code);return;}throw new RuntimeException('Did not reject '.$code);}
$GLOBALS['services']=['workshopservice'=>new workshopservice(),'workshoppolicy'=>new workshoppolicy(),'workshoprenderer'=>new class{function text($key){return $key;}},'user'=>new class{function userId(){return 'owner';}function isLoggedIn(){return true;}function isAdmin(){return true;}function isLecturer(){return true;}}];
$s=new examservice();$q=['stem'=>'Which plant flowers?','options'=>['Tree','Rock','Cloud','Sand'],'correctIndex'=>0,'sourceBasis'=>'Evidence ONLY for the marking sheet.'];
$set=['id'=>'chapter1','examid'=>'exam1','ownerid'=>'owner','title'=>'Chapter 1 🌿','version'=>3,'questions_json'=>json_encode([$q,$q+['included'=>false],$q])];
$row=['id'=>'exam1','ownerid'=>'owner','title'=>'Ecology exam','version'=>1,'content_json'=>json_encode($s->emptyContent())];
$c=$s->add($row,$set,['0','0','2']);check(count($c['questions'])===2,'duplicate indices collapsed');check($c['questions'][0]['sourceVersion']===3,'revision preserved');
$row['content_json']=json_encode($c);$set['title']='Changed';$set['questions_json']=json_encode([$q+['included'=>false]]);
check($s->content($row)['questions'][0]['chapter']==='Chapter 1 🌿','independent snapshot');
rejects(fn()=>$s->add($row,$set,['0']),'exam_selection');
$wrong=$set;$wrong['examid']='other-exam';rejects(fn()=>$s->add($row,$wrong,['0']),'exam_wrong_chapter');
$foreign=$set;$foreign['ownerid']='other';rejects(fn()=>$s->add($row,$foreign,['0']),'not_found');
rejects(fn()=>$s->revision($row,2),'exam_changed');
$entries=[];foreach($c['questions'] as $i=>$entry)$entries[$entry['id']]=['keep'=>'1','order'=>(string)(2-$i),'marks'=>(string)($i+1)];
$input=['complete'=>'1','instructions'=>"Answer all questions.\nChoose one option.",'headings'=>'1','reviewed'=>'1','entries'=>$entries];
$edited=$s->edit($row,$input);check($edited['questions'][0]['sourceIndex']===2,'order changed');check(array_sum(array_column($edited['questions'],'marks'))===3,'marks total');
$truncated=$input;unset($truncated['complete']);rejects(fn()=>$s->edit($row,$truncated),'exam_invalid');
$bad=$input;$bad['entries'][array_key_first($entries)]['marks']='-1';rejects(fn()=>$s->edit($row,$bad),'exam_invalid');
$bad=$input;unset($bad['entries'][array_key_first($entries)]);rejects(fn()=>$s->edit($row,$bad),'exam_invalid');
$row['content_json']=json_encode($edited);$paper=implode("\n",$s->lines($row));$key=implode("\n",$s->lines($row,true));
check(!str_contains($paper,'Evidence ONLY'),'no evidence leaked into paper');check(str_contains($key,'Evidence ONLY'),'key evidence');check(str_contains($paper,'1. Which')&&str_contains($paper,'2. Which'),'consecutive numbering');check(str_contains($key,'exam_total: 3'),'key total');check(!str_contains($paper,'draft_notice'),'reviewed paper');
$edited['headings']=false;$row['content_json']=json_encode($edited);check(!str_contains(implode("\n",$s->lines($row)),'Chapter 1'),'optional headings');check(str_contains(implode("\n",$s->lines($row,true)),'Chapter 1'),'key retains source chapter');
$export=new workshopexport();$GLOBALS['services']['workshopexport']=$export;$path=tempnam(sys_get_temp_dir(),'exam-test-');
try{file_put_contents($path,$s->odt($row));$zip=new ZipArchive();check($zip->open($path)===true,'ODT opens');$xml=$zip->getFromName('content.xml');$dom=new DOMDocument();check($dom->loadXML($xml,LIBXML_NONET),'valid XML');check(!str_contains($xml,'Evidence ONLY'),'ODT paper no answers');$zip->close();}finally{unlink($path);}
check(str_contains($export->disposition($row,'odt',true,'-marking-sheet'),'Ecology exam-marking-sheet.odt'),'marking filename');
$input['entries'][array_key_first($entries)]['keep']='0';check(count($s->edit($row,$input)['questions'])===1,'remove from exam only');
$row['content_json']=json_encode($s->emptyContent());rejects(fn()=>$s->lines($row),'exam_empty');
$GLOBALS['services']['examstore']=new class{function one($id){return ['ownerid'=>'other'];}};rejects(fn()=>$s->read('foreign'),'not_found');
echo "PASS $checks exam assembly, ownership, revision, marking and ODT checks.\n";

$large=$s->emptyContent();$entry=$c['questions'][0];
for($i=0;$i<200;$i++){$copy=$entry;$copy['id']='q'.$i;$large['questions'][]=$copy;}
$row['content_json']=json_encode($large);$set['id']='different-chapter';$set['questions_json']=json_encode([$q]);
rejects(fn()=>$s->add($row,$set,['0']),'exam_limit');
$selection=$s->emptyContent();$selection['questions']=[$entry];$row['content_json']=json_encode($selection);
$set['id']='chapter1';$set['questions_json']=json_encode([$q,$q+['included'=>false],$q]);
rejects(fn()=>$s->add($row,$set,['1']),'exam_selection');
echo "PASS exam size bound and excluded source question rejection.\n";

$mix=$s->emptyContent();
for($i=0;$i<17;$i++)$mix['questions'][]=['id'=>'mix'.$i,'sourceSetId'=>'chapter1','sourceIndex'=>$i,'chapter'=>'Chapter','marks'=>1,'question'=>$q];
$original=$mix;$mixed=$s->mixAnswers($mix);$counts=[0,0,0,0];
foreach($mixed['questions'] as $i=>$entry){
 $answer=$entry['question'];++$counts[$answer['correctIndex']];
 check($answer['options'][$answer['correctIndex']]===$q['options'][$q['correctIndex']],'correct answer text preserved');
 $options=$answer['options'];$expected=$q['options'];sort($options);sort($expected);check($options===$expected,'every option preserved');
 check($entry['sourceIndex']===$i,'source reference preserved');
}
check(max($counts)-min($counts)<=1,'answer positions balanced');check($mix===$original,'input snapshots unchanged');
$partial=$s->mixAnswers($mixed,16);check(array_slice($partial['questions'],0,16)===array_slice($mixed['questions'],0,16),'adding leaves previous option order unchanged');
$row['content_json']=json_encode($mixed);
check($s->lines($row)===$s->lines($row),'repeated paper exports stable');
$key=$s->lines($row,true);
foreach($mixed['questions'] as $entry){$a=$entry['question'];check(in_array(chr(65+$a['correctIndex']).'. '.$a['options'][$a['correctIndex']],$key,true),'marking key follows saved order');}
echo "PASS mixed answer positions, stable exports and matching marking key.\n";
