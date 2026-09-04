<?php
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$service=file_get_contents($root.'/classes/teachinginsights_class_inc.php');
$register=file_get_contents($root.'/register.conf');
$checks=array(
 'dashboard folds insights into each course card'=>!str_contains($controller,"setVar('teachingInsights'")&&str_contains($service,'function renderCard'),
 'courses remain author scoped'=>str_contains($service,'getContextWhereLecturer($userId)'),
 'real student totals'=>str_contains($service,'getContextStudents($code)'),
 'progress averages learner journeys'=>str_contains($service,"getObject('learningjourney','contextcontent')")&&str_contains($service,'$sum/$count'),
 'marking queue uses provider contracts'=>str_contains($service,"getObject('assessmentproviderregistry','gradebook')")&&str_contains($service,"==='submitted'"),
 'future actions have stable values'=>str_contains($service,"['provider'].'|'")&&str_contains($service,"['activity']")&&str_contains($service,'disabled title='),
 'visible author and learner roles are abstracted'=>str_contains($register,'Your [-author-] overview')&&str_contains($register,'[-readonlys-]'),
);
foreach($checks as $label=>$passed){if(!$passed){fwrite(STDERR,"FAIL: $label\n");exit(1);}}
echo "PASS: My Teaching provides role-safe course insight placeholders.\n";
?>
