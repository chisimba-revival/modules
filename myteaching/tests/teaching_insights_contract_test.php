<?php
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$service=file_get_contents($root.'/classes/teachinginsights_class_inc.php');
$register=file_get_contents($root.'/register.conf');
$checks=array(
 'dashboard folds insights into each course card'=>!str_contains($controller,"setVar('teachingInsights'")&&str_contains($service,'function renderCard'),
 'courses remain author scoped'=>str_contains($service,'getContextWhereLecturer($userId)'),
 'real student totals'=>str_contains($service,'getContextStudents($code)'),
 'dual-role authors are excluded from learner totals'=>str_contains($service,'getContextLecturers($code)')&&str_contains($service,'!isset($authors[$id])'),
 'progress averages learner journeys'=>str_contains($service,"getObject('learningjourney','contextcontent')")&&str_contains($service,'$sum/$count'),
 'marking queue uses provider contracts'=>str_contains($service,"getObject('assessmentproviderregistry','gradebook')")&&str_contains($service,"==='submitted'"),
 'providers may supply their exact review queue'=>str_contains($service,"getOutstandingReviewCount"),
 'assessment actions have stable values and launch targets'=>str_contains($service,"['provider'].'|'")&&str_contains($service,"['activity']")&&str_contains($service,"getLaunchTarget')")&&str_contains($service,"['url']"),
 'visible author and learner roles are abstracted'=>str_contains($register,'Your [-author-] overview')&&str_contains($register,'[-readonlys-]'),
);
foreach($checks as $label=>$passed){if(!$passed){fwrite(STDERR,"FAIL: $label\n");exit(1);}}
echo "PASS: My Teaching provides role-safe course insight placeholders.\n";
?>
