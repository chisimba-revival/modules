<?php


//set the layout of the choosequestiontype template
$this->setLayoutTemplate('mcqtests_layout_tpl.php');


//Load the classes for the template

$this->loadclass('htmltable','htmlelements');
$this->loadclass('htmlheading','htmlelements');
$this->loadclass('geticon','htmlelements');
$this->loadclass('link','htmlelements');
$this->loadClass('label', 'htmlelements');
$this->loadClass('fieldsetex', 'htmlelements');

$this->dbQuestions = $this->newObject('dbquestions');


//Set the language items
$choosetype=$this->objLanguage->languageText('mod_mcqtests_choosetype','mcqtests');
$typeLabel=$this->objLanguage->languageText('mod_mcqtests_typelabel','mcqtests');
$mcqtestLabel=$this->objLanguage->languageText('mod_mcqtests_mcqtestlabel','mcqtests');
$clozetestLabel=$this->objLanguage->languageText('mod_mcqtests_clozetestlabel','mcqtests');
$freeformLabel=$this->objLanguage->languageText('mod_mcqtests_freeformlabel','mcqtests');
$selectLabel=$this->objLanguage->languageText('mod_mcqtests_selectlabel','mcqtests');
//get the addicon
$objIcon=$this->newObject('geticon', 'htmlelements');
$count = (is_countable($questions) ? count($questions) : 0);
if (empty($questions)) {
    $count = 0;
}

$batchOptions = new dropdown('qnoption');
$batchOptions->setId("qnoption");
$batchOptions->addOption('-', '[-Select question type-]');
$batchOptions->addOption('mcq', 'MCQ questions');
$batchOptions->addOption('freeform', 'Free form test entry questions');
$batchLabel = new label ('Select question type ', 'input_qnoptionlabel');

echo '<strong><h1>'.$test['name'].'</h1></strong>';
$fd=$this->getObject('fieldsetex','htmlelements');

$fd->addLabel('<strong>'.$batchLabel->show().'</strong>'.$batchOptions->show());
$fd->setLegend('Select question type');
$formmanager=$this->getObject('formmanager');

$questionContentStr='<div id="addquestion">'.$formmanager->createAddQuestionForm($test).'</div>';
$questionContentStr.='<div id="freeform">'.$formmanager->createAddFreeForm($test).'</div>';

$fd->addLabel($questionContentStr);
echo $fd->show();



?>
<?php $this->appendArrayVar('headerParams', $this->getJavaScriptFile('question-type-options.js', 'mcqtests')); ?>
