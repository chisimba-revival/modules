<?php
/** Local, disposable workspace persistence and boundary checks. No AI calls. */
if(PHP_SAPI!=='cli'||getenv('QUESTIONWORKSHOP_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$e=new engine();
$exams=$e->getObject('examstore','mcqgenerator');$sets=$e->getObject('workshopstore','mcqgenerator');$service=$e->getObject('examservice','mcqgenerator');$db=$e->getDbObj();$owner='workspace-test-'.bin2hex(random_bytes(5));$ids=[];$parents=[];
function expect($ok,$label){if(!$ok)throw new RuntimeException($label);}
function denied($fn,$code){try{$fn();}catch(DomainException $e){expect($e->getMessage()===$code,$code);return;}throw new RuntimeException('Expected '.$code);}
try{
 $a=$parents[]=$exams->createExam($owner,'First exam',$service->emptyContent());$b=$parents[]=$exams->createExam($owner,'Elective 🌿',$service->emptyContent());
 $source=trim(str_repeat('Synthetic evidence. ',10));$q=['stem'=>'Which plant?','options'=>['Tree','Rock','Cloud','Sand'],'correctIndex'=>0,'sourceBasis'=>'Synthetic evidence.'];
 $s=$ids[]=$sets->createSet($owner,'Chapter 1',$source,[],3,$a);$sets->finish($sets->one($s),[$q]);
 expect(count($sets->owned($owner,1,$a))===1&&!$sets->owned($owner,1,$b),'Lists isolated');
 expect(count($sets->examChapters($owner,1,$a))===1&&!$sets->examChapters($owner,1,$b),'Picker isolated');
 expect(!$sets->owned('other',1,$a)&&!$sets->examChapters('other',1,$a),'Other owner cannot list');
 denied(fn()=>$sets->createSet('other','Wrong parent',$source,[],3,$a),'not_found');
 denied(fn()=>$sets->createSet($owner,'Missing parent',$source,[],3,'missing'),'not_found');
 denied(fn()=>$exams->removeOwned($exams->one($a)),'exam_has_chapters');
 expect($sets->one($s)['source_text']===$source,'Delete guard preserves source');
 $before=$sets->one($s);$db->exec("UPDATE tbl_questionworkshop_sets SET examid='' WHERE id=".$db->quote($s,'text'));
 $exams->assignLegacySets();$linked=$sets->one($s);expect(in_array($linked['examid'],[$a,$b],true),'Legacy same-owner parent');
 $exams->assignLegacySets();expect($sets->one($s)===$linked,'Repeat migration unchanged');
 foreach($before as $key=>$value)if($key!=='examid')expect($linked[$key]===$value,'Legacy data preserved');
 // Migration also handles an owner who never assembled an exam.
 $legacyOwner=$owner.'-legacy';$old=$exams->createExam($legacyOwner,'Temporary',$service->emptyContent());
 $l=$ids[]=$sets->createSet($legacyOwner,'Legacy chapter',$source,[],3,$old);
 $db->exec("UPDATE tbl_questionworkshop_sets SET examid='' WHERE id=".$db->quote($l,'text'));$exams->removeOwned($exams->one($old));
 $exams->assignLegacySets();$new=$parents[]=$sets->one($l)['examid'];expect($exams->one($new)['ownerid']===$legacyOwner,'Missing parent created for correct owner');
 echo "PASS workspace lists, picker isolation, owner/missing-parent denial, deletion safety, Unicode and repeatable legacy migration with/without existing exams.\n";
}finally{
 foreach($ids as $id){$row=$sets->one($id);if($row)$sets->removeOwned($id,$row['ownerid'],(int)$row['version']);}
 foreach($parents as $id){$row=$exams->one($id);if($row)$exams->removeOwned($row);}
}
