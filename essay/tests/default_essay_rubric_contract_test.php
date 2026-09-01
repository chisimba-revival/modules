<?php
/** Contract test for the versioned default Essay rubric. @author Derek Keats */
$root = dirname(__DIR__);
$source = file_get_contents($root.'/classes/essaydefaultrubric_class_inc.php');
foreach (array('essay-default-rubric-v1','Response to the task and argument','Knowledge and accuracy','Analysis and reasoning','Evidence and support','Organisation and coherence','Academic expression') as $expected) {
    if (strpos($source, $expected) === false) { fwrite(STDERR, "Missing default rubric content: {$expected}\n"); exit(1); }
}
echo "Default Essay rubric contract passed.\n";
