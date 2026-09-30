<?php
/** Pure identity and metadata checks: no database and no AI requests. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/bankquestion_class_inc.php';
$c=new bankquestion();
function check($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
$q=['stem'=>'<p>Which plant &amp; animal?</p>','type'=>'mcq','mark'=>1,'answers'=>[['answer'=>'Grass','correct'=>1],['answer'=>'Stone','correct'=>0]],'metadata'=>['rationale'=>'Evidence retained']];
$copy=$q;$copy['stem']=" WHICH  plant & animal? ";$copy['answers']=array_reverse($copy['answers']);
check($c->fingerprint($q)===$c->fingerprint($copy),'formatting, whitespace and answer order do not duplicate questions');
$copy['answers'][0]['correct']=1;$copy['answers'][1]['correct']=0;
check($c->stemKey($q)===$c->stemKey($copy)&&$c->fingerprint($q)!==$c->fingerprint($copy),'different correct answers remain a conflict');
$copy=$q;$copy['stem'].='<img src="plant-a.png">';$other=$copy;$other['stem']=str_replace('plant-a','plant-b',$other['stem']);
check($c->fingerprint($copy)!==$c->fingerprint($other),'different question images retain distinct identities');
$generated=['stem'=>'<script>alert(1)</script>','options'=>['A','B','C','D'],'correctIndex'=>2,'sourceBasis'=>'Exact source evidence','rationale'=>'Because C','custom'=>['difficulty'=>'medium']];
$bank=$c->generated($generated,['id'=>'set','version'=>3,'title'=>'Plants'],1,'Chapter 1');
check($bank['metadata']['candidate']===$generated&&$bank['chapters']===['Chapter 1'],'all generated metadata and chapter tags are retained');
check(str_contains($bank['stem'],'&lt;script&gt;'),'generated plain text stays inert');
try{$bad=$q;$bad['answers'][0]['correct']=0;$c->validate($bad);throw new RuntimeException('Accepted invalid answers');}catch(DomainException $e){check($e->getMessage()==='bank_invalid','invalid answer sets rejected');}

check(html_entity_decode($c->testText('Grass 🌱'),ENT_QUOTES|ENT_HTML5,'UTF-8')==='Grass 🌱','supplementary characters survive legacy test storage');
