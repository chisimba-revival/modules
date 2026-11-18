<?php
/** Behaviour checks for the portable semantic outline. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=null){return new quizinputplan();}} class controller extends ChisimbaObject {}
require dirname(__DIR__).'/classes/knowledgemapgraphservice_class_inc.php';
require dirname(__DIR__).'/classes/knowledgemapblueprint_class_inc.php';
require dirname(__DIR__,2).'/mcqtests/classes/quizinputplan_class_inc.php';
$graph=new knowledgemapgraphservice(); $builder=new knowledgemapblueprint();
function check($ok,$message) { if (!$ok) throw new RuntimeException($message); }
function document(array $rows) {
    $nodes=[]; $edges=[];
    foreach ($rows as $i=>$row) {
        [$id,$role,$parent,$title]=$row;
        $nodes[]=['id'=>$id,'type'=>$role,'title'=>$title,'description'=>$row[4]??'','presentation'=>['side'=>'right']];
        if ($parent) $edges[]=['id'=>'rel-'.$id,'type'=>'contains','from'=>$parent,'to'=>$id,'order'=>$i];
    }
    return ['rootId'=>'root','nodes'=>$nodes,'relationships'=>$edges];
}
function build($rows) { global $graph,$builder; $normal=$graph->normalise(document($rows)); check($normal['valid'],'Graph valid'); return $builder->build($normal['document']); }
$rows=[['root','course',null,'Ecology','Ignored course note'],['s','section','root','Ecosystems','Ignored section note'],
 ['d','description','s','Section description'],['c','chapter','s','Forests'],['p','page','c','Layers','Ignored page note'],
 ['q','quiz','c','Check your knowledge'],['u','standard','root','Planning only']];
$result=build($rows);check($result['valid'],'Valid outline');
check($result['outline']['description']==='' && $result['outline']['children'][0]['description']==='Section description','Explicit descriptions only');
check($result['outline']['children'][0]['children'][0]['children'][0]['body']==='','Pages are empty');
check($result['outline']['children'][0]['children'][0]['children'][1]['role']==='quiz','Quiz follows page');
check($result['excluded']===['Planning only'] && $result['hasContent'],'Excluded nodes and protection');
$rows[]=['text','content','p','Content label',"Text <script>alert(1)</script>\nSecond line"];
$result=build($rows);check(str_contains($result['outline']['children'][0]['children'][0]['children'][0]['body'],'<script>'),'Blueprint retains plain text');
$result=build([['root','course',null,'Course'],['a','page','root','First'],['b','page','root','Second'],['c','chapter','root','Middle'],['d','page','root','Last']]);
check($result['valid'] && !$result['hasContent'],'Title-only outline remains empty');
check(count($result['outline']['children'])===3 && $result['outline']['children'][0]['generated'] && $result['outline']['children'][2]['generated'],'Container chapters preserve interleaved ordering');
check(count($result['outline']['children'][0]['children'])===2,'Adjacent pages share container');
foreach ([
 [['root','standard',null,'Course']],
 [['root','course',null,'Course'],['c','content','root','Invalid']],
 [['root','course',null,'Course'],['u','standard','root','Notes'],['p','page','u','Hidden page']],
 [['root','course',null,'Course'],['p','page',null,'Detached']],
 [['root','course',null,'Course'],['q','quiz','root',str_repeat('x',61)]],
 [['root','course',null,'Course'],['a','description','root','One'],['b','description','root','Two']]
] as $bad) check(!build($bad)['valid'],'Invalid semantic hierarchy rejected');
foreach (knowledgemapblueprint::ROLES as $role) { $normal=$graph->normalise(document([['root',$role,null,'Title']]));check($normal['document']['nodes'][0]['type']===$role,'Semantic role round trip'); }
echo "PASS: explicit content, title-only pages/quizzes, role round trips, ordered containers, exclusions and invalid hierarchy\n";

$source=str_repeat('Grasses have jointed stems and narrow leaves. ',6000);
$plan=(new quizinputplan())->build($source);check(implode('',array_column($plan['requests'],'source'))===trim($source),'Long source has no truncation');check(array_sum(array_column($plan['requests'],'count'))===$plan['count']&&$plan['count']<=30,'Bounded complete coverage');
$rows=[['root','course',null,'Course'],['c','chapter','root','Review'],['q','quiz','c','Quiz'],['input','quiz_input','q','Source',$source],['p','page','c','Page']];
$result=build($rows);check($result['valid']&&$result['hasContent'],'Quiz input protects generated course');$children=$result['outline']['children'][0]['children'];check($children[0]['role']==='page'&&$children[1]['role']==='quiz','Quiz moves to chapter end');check($children[1]['quizInput']===trim($source)&&$children[1]['quizQuestionCount']===30,'Full source reaches quiz');
foreach([['q','quiz','root','Quiz'],['q','quiz','s','Quiz']] as $bad)check(!build([['root','course',null,'Course'],['s','section','root','Section'],$bad])['valid'],'Quiz needs explicit chapter');
$rows[3][4]='too short';check(!build($rows)['valid'],'Short quiz input rejected');
echo "PASS: chapter-only quizzes, long lossless quiz input, bounded request plan, end-of-chapter order and input validation\n";

$unusual=str_repeat(str_repeat('x',6000).' ',49);$plan=(new quizinputplan())->build($unusual);check(count($plan['requests'])<=30&&implode('',array_column($plan['requests'],'source'))===trim($unusual),'Long words still fit bounded requests without truncation');

$direct=[['root','course',null,'Course'],['c','chapter','root','Review'],['q','quiz','c','Quiz',$source]];
$result=build($direct);$quiz=$result['outline']['children'][0]['children'][0];
check($result['valid']&&$result['hasContent']&&$quiz['quizInput']===trim($source),'Quiz Note alone is lossless source and protects generation');
check($quiz['quizQuestionCount']===30,'Quiz Note plans bounded requests');
$direct[2][4]='';$result=build($direct);check($result['valid']&&!$result['hasContent']&&$result['outline']['children'][0]['children'][0]['quizInput']==='','Blank Quiz Note remains empty');
$direct[2][4]='short';check(!build($direct)['valid'],'Short direct Quiz Note is validated');
$direct[2][4]=str_repeat('a',160000);$direct[]=['legacy','quiz_input','q','Source',str_repeat('b',150000)];check(!build($direct)['valid'],'Combined legacy and direct source respects aggregate limit');
echo "PASS: direct Quiz Note sources generation, preserves empty quizzes, validates bounds and protects existing courses\n";

// Imported/root relationship order may interleave both visual sides.
$clock=document([['root','course',null,'Course'],['l1','chapter','root','Left top'],['r1','chapter','root','Right top'],['l2','chapter','root','Left bottom'],['r2','chapter','root','Right bottom'],['p1','page','l1','First page'],['p2','page','l1','Second page']]);
foreach($clock['nodes'] as &$n)if(in_array($n['id'],['l1','l2'],true))$n['presentation']['side']='left';unset($n);
$clockBefore=$clock;$result=$builder->build($graph->normalise($clock)['document']);
check(array_column($result['outline']['children'],'sourceId')===['r1','r2','l2','l1'],'Clockwise root branch order');
check(array_column($result['outline']['children'][3]['children'],'sourceId')===['p1','p2'],'Left-side chapter pages keep their order');
check($clock===$clockBefore,'Preview does not mutate the map');
foreach($clock['nodes'] as &$n)unset($n['presentation']);unset($n);
$result=$builder->build($graph->normalise($clock)['document']);check(array_column($result['outline']['children'],'sourceId')===['l1','l2','r2','r1'],'Legacy automatic sides match editor alternation');
foreach($clock['nodes'] as &$n)$n['presentation']=['dir'=>'left'];unset($n);
$result=$builder->build($graph->normalise($clock)['document']);check(array_column($result['outline']['children'],'sourceId')===['r2','l2','r1','l1'],'Legacy direction and left-only clockwise order');
echo "PASS: clockwise top-level branches, preserved page order, legacy sides, and read-only preview\n";

$planner=new quizinputplan();
foreach([10,20,30,100] as $count){$plan=$planner->build(str_repeat('Ecology source text. ',80),$count);check($plan['count']===$count&&array_sum(array_column($plan['requests'],'count'))===$count,'Exact requested bank size');foreach($plan['requests'] as $request)check($request['count']<=10,'Bounded worker request');}
foreach([['bankCount'=>9],['bankCount'=>101],['drawCount'=>31],['drawCount'=>0],['selection'=>'anything'],['bankCount'=>30.5]] as $bad){try{$planner->settings($bad);throw new RuntimeException('Invalid settings accepted');}catch(InvalidArgumentException $e){check($e->getMessage()==='quiz_settings','Settings validation');}}
foreach(['fixed','per_attempt'] as $mode){
 $document=document([['root','course',null,'Course'],['c','chapter','root','Chapter'],['q','quiz','c','Quiz',str_repeat('Ecology source text. ',80)]]);
 $settings=['bankCount'=>20,'drawCount'=>8,'selection'=>$mode];$document['nodes'][2]['presentation']['quizSettings']=$settings;
 $normal=$graph->normalise($document);check($normal['document']['nodes'][2]['presentation']['quizSettings']===$settings,'Settings round trip');
 $result=$builder->build($normal['document']);$quiz=$result['outline']['children'][0]['children'][0];check($quiz['quizQuestionCount']===20&&$quiz['quizDrawCount']===8&&$quiz['quizSelection']===$mode,'Preview both modes and counts');
}
echo "PASS: exact requested bank sizes, bounded calls, invalid settings, both modes and portable settings\n";
