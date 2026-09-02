<?php
/** Contract checks for the learner Essay submission journey. @author Derek Keats */
$controller=file_get_contents(__DIR__.'/../controller.php');
$template=file_get_contents(__DIR__.'/../templates/content/upload_tpl.php');
$writer=file_get_contents(__DIR__.'/../templates/content/write_tpl.php');
$viewer=file_get_contents(__DIR__.'/../templates/content/view_essays_tpl.php');
$database=file_get_contents(__DIR__.'/../classes/dbessay_book_class_inc.php');
$checks=array(
 'direct native file input'=>str_contains($template,'type="file"')&&str_contains($template,'name="essayfile"'),
 'accessible upload label'=>str_contains($template,'for="essay-file"'),
 'skin action primitives'=>str_contains($template,'chisimba-form-actions')&&str_contains($template,"getObject('iconservice','ui')"),
 'booking ownership enforced'=>str_contains($controller,"studentid='")&&str_contains($controller,"context='"),
 'uploaded file is persisted'=>str_contains($controller,"uploadFile('essayfile')")&&str_contains($controller,"'studentfileid' => \$fileId"),
 'large in-browser writing surface'=>str_contains($writer,'contenteditable="true"')&&str_contains($writer,'chisimba-longform-editor'),
 'draft autosave is visible'=>str_contains($writer,'data-save-state')&&str_contains($writer,"action'=>'savedraft")&&str_contains($writer,'setInterval(save,15000)'),
 'concurrent edits remain dirty'=>str_contains($writer,'revision!==savingRevision'),
 'final submission is deliberate'=>str_contains($writer,'Submit for marking')&&str_contains($writer,"action'=>'submitwritten"),
 'written and upload choices coexist'=>str_contains($viewer,'Write essay')&&str_contains($viewer,'Upload document instead'),
 'written submission has independent snapshot'=>str_contains($database,"'draft_html'")&&str_contains($database,"'submission_html'")&&str_contains($database,"'submission_mode' => 'written'"),
 'learner draft endpoints use CSRF'=>str_contains($controller,'DRAFT_CSRF')&&str_contains($controller,'SUBMIT_CSRF')&&str_contains($controller,'->consume('),
 'PHP 8.5-safe HTML purification'=>str_contains($controller,'objEngine->purifier->purify')&&!str_contains($controller,"getObject('htmlcleaner'"),
);
$failed=false; foreach($checks as $name=>$ok){echo ($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;$failed=$failed||!$ok;} exit($failed?1:0);
