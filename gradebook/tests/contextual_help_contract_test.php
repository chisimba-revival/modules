<?php
$base = dirname(__DIR__);
$provider = file_get_contents($base.'/classes/helpcontent_class_inc.php');
$register = file_get_contents($base.'/register.conf');
$templates = file_get_contents($base.'/templates/content/assessment_plan_tpl.php')
    .file_get_contents($base.'/templates/content/assessment_sheet_tpl.php');
$checks = array(
    strpos($provider, "isContextMember('Lecturers')") !== false,
    strpos($provider, 'isAdmin()') !== false,
    substr_count($templates, "show('gradebook', 'assessment-plan-and-sheet')") === 2,
    strpos($register, 'DEPENDS: help') !== false,
    strpos($register, 'Plan selects; Sheet weights') !== false,
    strpos($register, 'Record marks in the activity') !== false,
    strpos($register, '[-context-]') !== false,
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "Gradebook contextual Help contract failed.\n"); exit(1); }
echo "Gradebook contextual Help contract passed.\n";
?>
