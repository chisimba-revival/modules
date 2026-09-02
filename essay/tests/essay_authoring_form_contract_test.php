<?php
/** Contract test for long-form Essay authoring. @author Derek Keats */
$root = dirname(__DIR__);
$template = file_get_contents($root.'/templates/content/manage_addeditessay_tpl.php');
$controller = file_get_contents($root.'/classes/essaymanagementbase_class_inc.php');
foreach (array("newObject('htmlarea'", "name = 'notes'", "name = 'model_essay'", 'chisimba-form--wide', 'Default essay rubric') as $expected) {
    if (strpos($template, $expected) === false) { fwrite(STDERR, "Missing authoring contract: {$expected}\n"); exit(1); }
}
if (strpos($controller, "getParam('model_essay'") === false) { fwrite(STDERR, "Model essay is not persisted.\n"); exit(1); }
echo "Essay authoring form contract passed.\n";
