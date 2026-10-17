<?php
/**
 * Native numerical question editor.
 * @package mcqtests
 */

// set up layout template
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$numericalformmanager = $this->getObject('numerical_question');
$title = ($mode ?? '') === 'edit'
    ? $this->objLanguage->languageText('mod_mcqtests_editquestion', 'mcqtests')
    : $this->objLanguage->languageText('mod_mcqtests_phraseadda', 'mcqtests').' '.$this->objLanguage->languageText('mod_mcqtests_numericalqns', 'mcqtests');
echo '<h1>'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'</h1>';

$testid = $this->getParam('id');
$questionId = null;
if(($mode ?? '') == 'edit') {
    $edit = true;
    $questionId = $this->getParam('questionId');
} else {
    $edit = false;
}
// display the form for editing this question
$questionContentStr = '<div id="numericalquestions">'.$numericalformmanager->numericalQForm($testid, ($data ?? null), $edit, $questionId).'</div>';

echo $questionContentStr;
?>