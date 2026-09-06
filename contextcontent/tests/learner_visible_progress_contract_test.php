<?php
$root=dirname(__DIR__);
$order=file_get_contents($root.'/classes/db_contextcontent_order_class_inc.php');
$journey=file_get_contents($root.'/classes/learningjourney_class_inc.php');
$completion=file_get_contents($root.'/classes/coursecompletioneligibilityservice_class_inc.php');
$checks=array(
    'learner count follows visible chapters'=>str_contains($order,'getNumLearnerContextPages')
        && str_contains($order,"c.visibility='Y'"),
    'dashboard uses learner count'=>str_contains($journey,'getNumLearnerContextPages($contextCode)'),
    'completion uses learner count'=>str_contains($completion,'getNumLearnerContextPages($contextCode)'),
    'first page follows chapter order'=>str_contains($order,'tbl_contextcontent_chaptercontext.chapterorder')
        && str_contains($order,"tbl_contextcontent_chaptercontext.visibility = \\'Y\\'"),
);
foreach($checks as $name=>$passed){if(!$passed){fwrite(STDERR,"FAIL: {$name}\n");exit(1);}}
echo "PASS: student progress excludes pages that students cannot enter.\n";
?>
