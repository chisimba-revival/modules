<?php
/** Render the chapter picker from saved snapshots, including off-page chapters. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($name,$module=null){return new CountRenderer();}}
require __DIR__.'/../classes/examservice_class_inc.php';
class CountRenderer {
 function escape($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
 function text($key){return $key;}
 function __call($name,$args){return '';}
}
class CountPage {
 function setLayoutTemplate($s){}
 function getParam($s){return '';}
 function uri($p,$m){return '?'.http_build_query($p);}
 function getResourceUri($s,$m){return $s;}
 function getObject($name,$module=null){return $name==='examservice'?new examservice():new CountRenderer();}
 function render(array $questions,$examQuestionType='mcq'){
  $examError='';$examDraft=null;$examChapter=null;$examToken='test';$examPage=1;
  $examRow=['id'=>'exam','title'=>'Test exam','version'=>1,'content_json'=>json_encode(['questions'=>$questions,'instructions'=>'','headings'=>true,'reviewed'=>false])];
  $examSets=[['id'=>'a','title'=>'Chapter A'],['id'=>'b','title'=>'Chapter B'],['id'=>'unused','title'=>'Unused']];
  ob_start();include __DIR__.'/../templates/content/exams_tpl.php';return ob_get_clean();
 }
}
function entry($id,$source){return ['id'=>$id,'sourceSetId'=>$source,'chapter'=>$source,'marks'=>1,'question'=>['stem'=>'Test','options'=>['A','B','C','D'],'correctIndex'=>0]];}
function verify($ok,$message){if(!$ok)throw new RuntimeException($message);}
$page=new CountPage();$questions=[entry('1','a'),entry('2','a'),entry('3','b'),entry('4','off-page')];
$html=$page->render($questions);
verify(str_contains($html,'exam_saved_count: <span class="chisimba-pill chisimba-pill--success">4</span>'),'Total includes off-page sources');
verify(str_contains($html,'aria-label="2 exam_in_exam" title="2 exam_in_exam">2</span>'),'Chapter A has two saved questions');
verify(str_contains($html,'<span>Unused</span><span class="chisimba-pill" aria-label="0 exam_in_exam" title="0 exam_in_exam">0</span></a>'),'Unused chapter shows an explicit zero badge');
array_shift($questions);$html=$page->render($questions);
verify(!str_contains($html,'aria-label="2 exam_in_exam"'),'Removal reduces chapter count');
verify(str_contains($html,'exam_saved_count: <span class="chisimba-pill chisimba-pill--success">3</span>'),'Removal reduces total');
$html=$page->render([]);verify(substr_count($html,'aria-label="0 exam_in_exam"')===3,'Empty exam shows zero on every chapter');
$short=entry('short','a');$short['question']['type']='short_answer';$short['question']['markingPoints']='Two valid points.';
$html=$page->render([$short],'short_answer');
verify(str_contains($html,'aria-label="1 exam_in_exam" title="1 exam_in_exam">1</span>'),'Short-answer picker shows its saved question count');
verify(substr_count($html,'aria-label="0 exam_in_exam"')===2,'Unused short-answer sets retain zero badges');
echo "PASS saved chapter badges, both question types, off-page totals, removals and empty exam.\n";
