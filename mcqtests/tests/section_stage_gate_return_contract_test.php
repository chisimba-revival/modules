<?php
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$result=file_get_contents($root.'/templates/content/showtest_tpl.php');
$checks=array(
    'section return persists through attempt'=>substr_count($controller,'stage_gate_return_section')>=8,
    'passed gate opens informational section'=>str_contains($result,"'action' => 'viewsection'")
        &&str_contains($result,'stageGateReturnSection'),
    'section continuation uses systext'=>str_contains(
        $result,
        'mod_mcqtests_stage_gate_continue_next_section'
    )
);
foreach($checks as $name=>$passed){if(!$passed){fwrite(STDERR,"FAIL: {$name}\n");exit(1);}}
echo "PASS: stage gates can return to an informational section\n";
