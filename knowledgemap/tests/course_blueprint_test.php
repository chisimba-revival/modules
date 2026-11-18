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
        $nodes[]=['id'=>$id,'type'=>$role,'title'=>$title,'description'=>$row[4]??''];
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
$result=build($rows);check($result['valid']&&$result['hasContent'],'Quiz input protects generated course');$children=$result['outline']['children'][0]['children'];check($children[0]['role']==='page'&&$children[1]['role']==='quiz','Quiz moves to chapter end');check($children[1]['quizInput']===trim($source)&&$children[1]['quizQuestionCount']===$plan['count'],'Full source reaches quiz');
foreach([['q','quiz','root','Quiz'],['q','quiz','s','Quiz']] as $bad)check(!build([['root','course',null,'Course'],['s','section','root','Section'],$bad])['valid'],'Quiz needs explicit chapter');
$rows[3][4]='too short';check(!build($rows)['valid'],'Short quiz input rejected');
echo "PASS: chapter-only quizzes, long lossless quiz input, bounded request plan, end-of-chapter order and input validation\n";
