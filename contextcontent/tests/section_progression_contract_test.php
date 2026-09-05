<?php
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$service=file_get_contents($root.'/classes/sectionprogressionservice_class_inc.php');
$schema=file_get_contents($root.'/sql/tbl_contextcontent_chaptercontext.sql');
$checks=array(
 'chapter placement supports optional section'=>strpos($schema,"'sectionid'")!==FALSE && strpos($schema,"'default' => ''")!==FALSE,
 'legacy overview has explicit opt-in branch'=>strpos($controller,'if ($this->objSectionProgression->enabled($this->contextCode))')!==FALSE,
 'chapter direct access is guarded'=>substr_count($controller,'chapterDecision($this->contextCode')>=2,
 'section entry needs acknowledgement'=>strpos($service,"['available'])&&!empty(\$section['acknowledged'])")!==FALSE,
 'next section requires final chapter gate'=>strpos($service,'$this->objGates->chapterGate($contextCode,$last')!==FALSE,
 'managers retain authoring access'=>strpos($service,'$this->isManager($contextCode)')!==FALSE
);
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').': '.$name.PHP_EOL;if(!$ok){exit(1);}}
?>
