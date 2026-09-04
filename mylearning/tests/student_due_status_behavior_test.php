<?php
/** Verify one outcome label replaces deadline state after submission. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__) . '/classes/studentdueitems_class_inc.php';
$service=new studentdueitems();
$set=function($name,$value)use($service){$p=new ReflectionProperty($service,$name);$p->setAccessible(true);$p->setValue($service,$value);};
$set('language',new class {public function languageText($key,$module,$fallback){return $fallback;}});
$set('time',new class {public function siteTimezone(){return 'Africa/Johannesburg';}public function formatDateTime($date){return 'DUE DATE';}});
$set('icons',new class {public function render($name,$options){return '<svg></svg>';}});
$past=new DateTimeImmutable('yesterday',new DateTimeZone('Africa/Johannesburg'));
$base=array('id'=>'activity','course'=>'Course','providerLabel'=>'Essay','due'=>$past,'url'=>'');
$render=new ReflectionMethod($service,'render');$render->setAccessible(true);
$html=$render->invoke($service,array(
 $base+array('title'=>'Waiting','status'=>'submitted','markPercent'=>null),
 $base+array('title'=>'Result','status'=>'marked','markPercent'=>90.0),
));
foreach(array('Awaiting marking','Marked (90%)') as $expected){if(substr_count($html,$expected)!==1)throw new RuntimeException('Missing single state: '.$expected);}
foreach(array('>Submitted<','>Overdue<','dashboard-mark') as $forbidden){if(str_contains($html,$forbidden))throw new RuntimeException('Conflicting state: '.$forbidden);}
echo "PASS: submitted and marked schedule items each render one current state.\n";
