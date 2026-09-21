<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($name,$module=null){return $GLOBALS['services'][$name];}}
require dirname(__DIR__).'/classes/examservice_class_inc.php';
$s=new examservice();$GLOBALS['services']['workshoprenderer']=new class {function text($key){return $key;}};
function check($condition,$label){if(!$condition)throw new RuntimeException($label);}
$sa=['id'=>'s','sourceSetId'=>'source-s','sourceIndex'=>4,'chapter'=>'Chapter S','marks'=>5,'question'=>['type'=>'short_answer','stem'=>'Name two needs.','modelAnswer'=>'Water and light.','markingPoints'=>"Water: 2 marks\nLight (3 marks)",'sourceBasis'=>'Private evidence.']];
$mcq=['id'=>'m','sourceSetId'=>'source-m','sourceIndex'=>1,'chapter'=>'Chapter M','marks'=>8,'question'=>['stem'=>'Which is needed?','options'=>['Water','Rock','Fire','Ice'],'correctIndex'=>0,'sourceBasis'=>'Private evidence.']];
$raw=['questions'=>[$sa,$mcq],'instructions'=>'Answer both.','reviewed'=>true,'headings'=>false];$row=['title'=>'Saved mixed exam','content_json'=>json_encode($raw)];
$c=$s->content($row);check(array_column($c['questions'],'id')===['m','s'],'type section ordering');check(array_column($c['questions'],'marks')===[1,2],'fixed marks on legacy snapshots');check(!$c['reviewed'],'changed allocation needs review');check(json_decode($row['content_json'],true)===$raw,'reading cannot rewrite saved data');check($c['questions'][1]['question']===$sa['question'],'original answer and rubric retained');
$input=['complete'=>'1','instructions'=>'Saved.','reviewed'=>'1','entries'=>['m'=>['keep'=>'1','order'=>'9','marks'=>'100'],'s'=>['keep'=>'1','order'=>'1','marks'=>'100']]];
$edited=$s->edit($row,$input);check(array_column($edited['questions'],'id')===['m','s'],'submitted ordering cannot mix sections');check(array_column($edited['questions'],'marks')===[1,2],'forged marks ignored');
$row['content_json']=json_encode($edited);$paper=implode("\n",$s->lines($row));$key=implode("\n",$s->lines($row,true));check(strpos($paper,'exam_section_mcq')<strpos($paper,'exam_section_short'),'paper section headings');check(str_contains($paper,'1. Which')&&str_contains($paper,'2. Name'),'continuous numbering');check(!str_contains($paper,'Water and light.')&&!str_contains($paper,'Private evidence.'),'paper excludes answers and evidence');check(str_contains($key,'exam_two_mark_rubric')&&!str_contains($key,'3 marks'),'fixed two-mark guide');check(str_contains($key,'Water and light.')&&str_contains($key,'Private evidence.'),'guide retains answers and evidence');
$input['entries']['s']['keep']='0';check(count($s->edit($row,$input)['questions'])===1,'explicit removal only');
echo "PASS: section ordering, fixed marks, legacy preservation, explicit removal and answer-safe exports.\n";
