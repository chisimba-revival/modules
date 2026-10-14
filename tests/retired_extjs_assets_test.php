<?php
/** Guard removal of replaced teaching-module ExtJS assets. @author Derek Keats */
$root = dirname(__DIR__);
foreach (['assignment/resources/addedit_assignment.js', 'mcqtests/templates/content/choosequestiontype2_tpl.php', 'mcqtests/resources/js/chooseqtype.js', 'mcqtests/resources/js/main.js', 'mcqtests/resources/js/mcqdb.js', 'mcqtests/resources/js/calculateQ.js'] as $path) {
    if (file_exists($root . '/' . $path)) throw new RuntimeException('Retired asset returned: ' . $path);
}
$controller = file_get_contents($root . '/mcqtests/controller.php');
if (!preg_match('/case \'choosequestiontype2\':\s*return \$this->nextAction\(\'banks\'/s', $controller)) {
    throw new RuntimeException('Old picker URL must retain its native-bank redirect');
}
if (!is_file($root . '/assignment/templates/content/addedit_assignment_tpl.php')) {
    throw new RuntimeException('Native assignment editor missing');
}
echo "PASS: obsolete teaching assets absent; bank redirect and assignment editor retained\n";
