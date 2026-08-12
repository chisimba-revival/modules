<?php

$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('radio', 'htmlelements');
$this->loadClass('label', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('htmlHeading','htmlelements');
$this->loadClass('fieldset','htmlelements');
$this->loadClass('dropdown', 'htmlelements');

$header=new htmlheading();
$header->type=1;

if ($mode == 'edit') {
    $formaction = 'updatechapter';
    $areaTitle = $this->objLanguage->languageText('mod_contextcontent_editchapter','contextcontent').': <span class="chaptertitle">'.$chapter['chaptertitle'].'</span>';
} else {
    $areaTitle = $this->objLanguage->languageText('mod_contextcontent_addnewchapterin','contextcontent').' <span class="chaptertitle">'.$this->objContext->getTitle().'<span>';
    $formaction = 'savechapter';
}

$header->str=$areaTitle;
if ($this->getParam('stage_gate_saved', '') === '1') {
    echo '<div class="confirmation contextcontent-stage-gate-confirmation">'
        .$this->objLanguage->languageText('mod_contextcontent_stage_gate_saved', 'contextcontent', 'Stage gate saved.').'</div>';
}
//echo '<p>Todo: Allow User to place order of chapter</p>';

$form = new form ('addchapter', $this->uri(array('action'=>$formaction)));
$csrfInput = new hiddeninput('csrf_token', $contextContentCsrf);
$form->addToForm($csrfInput->show());
$table = $this->newObject('htmltable', 'htmlelements');

$title = new textinput('chaptertitle');
$title->size = 60;

if ($mode == 'edit') {
    $title->value = $chapter['chaptertitle'];
}

$label = new label ($this->objLanguage->languageText('mod_contextcontent_chaptertitle','contextcontent'), 'input_chaptertitle');
$table->startRow();
$table->addCell($label->show(), 150);
$table->addCell($title->show());
$table->endRow();



$radio = new radio ('visibility');
$radio->addOption('Y', ' '.$this->objLanguage->languageText('word_yes','system', 'Yes'));
$radio->addOption('N', ' '.$this->objLanguage->languageText('word_no','system', 'No'));
$radio->addOption('I', ' '.$this->objLanguage->languageText('mod_contextcontent_onlyshowintroduction','contextcontent'));

if ($mode == 'edit') {
    $radio->setSelected($chapter['visibility']);
} else {
    $radio->setSelected('Y');
}
$radio->setBreakSpace(' &nbsp; ');
$table->startRow();
$table->addCell("<br/>");
$table->endRow();

$table->startRow();
$table->addCell($this->objLanguage->code2Txt('mod_contextcontent_visibletostudents','contextcontent'));
$table->addCell($radio->show());
$table->endRow();

$table->startRow();
$table->addCell("<br/>");
$table->endRow();

$objPopupcal = $this->newObject('datepickajax', 'popupcalendar');
$startLabel=$this->objLanguage->languageText('mod_contextcontent_releasedate','contextcontent',"Release date");
$closeLabel=$this->objLanguage->languageText('mod_contextcontent_enddate','contextcontent',"End date");
/* *** start date & time *** */
// Set start date of test
if ($mode == 'edit') {
    $startField = $objPopupcal->show('startdate', 'yes', 'no', $chapter['releasedate']);
} else {
    $startField = $objPopupcal->show('startdate', 'yes', 'no', '');
}
$objLabel = new label('<b>'.$startLabel.':</b>', 'input_start');
$table->addRow(array(
    $objLabel->show() ,
    $startField
));
// Set closing date of test

if ($mode == 'edit') {
    $closeField = $objPopupcal->show('enddate', 'yes', 'no', $chapter['enddate']);
} else {
    $closeField = $objPopupcal->show('enddate', 'yes', 'no', '');
}
$objLabel = new label('<b>'.$closeLabel.':</b>', 'input_close');
$table->addRow(array(
    $objLabel->show() ,
    $closeField
));


$table->startRow();
$table->addCell("<br/>");
$table->endRow();

if ($mode == 'edit') {
    $stageGateTests = isset($stageGateTests) && is_array($stageGateTests) ? $stageGateTests : array();
    if (!empty($selectedStageGateIsInvalid)) {
        echo '<div class="error contextcontent-stage-gate-validation">'
            . htmlspecialchars($this->objLanguage->languageText('mod_contextcontent_stage_gate_existing_summative', 'contextcontent'), ENT_QUOTES, 'UTF-8')
            . '</div>';
    }
    $stageGate = new radio('stage_gate_enabled');
    $stageGate->addOption('0', ' '.$this->objLanguage->languageText('word_no', 'system', 'No'));
    $stageGate->addOption('1', ' '.$this->objLanguage->languageText('word_yes', 'system', 'Yes'));
    $stageGate->setSelected(!empty($chapter['stage_gate_enabled']) ? '1' : '0');
    $stageGate->setBreakSpace(' &nbsp; ');
    $testSelect = new dropdown('stage_gate_testid');
    $testSelect->addOption('', $this->objLanguage->languageText('mod_contextcontent_stage_gate_choose_test', 'contextcontent'));
    foreach ($stageGateTests as $stageGateTest) {
        if (!empty($stageGateTest['id']) && isset($stageGateTest['name'])) {
            $testSelect->addOption($stageGateTest['id'], $stageGateTest['name']);
        }
    }
    $testSelect->setSelected(isset($chapter['stage_gate_testid']) ? $chapter['stage_gate_testid'] : '');
    $passMark = new textinput('stage_gate_passmark');
    $passMark->size = 3;
    $passMark->value = !empty($chapter['stage_gate_passmark']) ? $chapter['stage_gate_passmark'] : '70';
    $table->addRow(array($this->objLanguage->languageText('mod_contextcontent_stage_gate_enabled', 'contextcontent'), $stageGate->show()));
    $table->addRow(array($this->objLanguage->languageText('mod_contextcontent_stage_gate_test', 'contextcontent'), $testSelect->show()));
    $table->addRow(array($this->objLanguage->languageText('mod_contextcontent_stage_gate_passmark', 'contextcontent'), $passMark->show()));
}


//$label = new label ($this->objLanguage->languageText('mod_contextcontent_aboutchapter_introduction','contextcontent'), 'input_aboutchapter');
$htmlArea = $this->newObject('htmlarea', 'htmlelements');
$htmlArea->name = 'intro';
$htmlArea->context = TRUE;

if ($mode == 'edit') {
    $htmlArea->value = $chapter['introduction'];
}

$table->startRow();
//$table->addCell($label->show());
$table->addCell('&nbsp;');
$table->addCell($htmlArea->show());
$table->endRow();



$form->addToForm($table->show());


$buttonLabel = $mode == 'edit'
    ? $this->objLanguage->languageText('mod_contextcontent_savechanges', 'contextcontent')
    : $this->objLanguage->languageText('mod_contextcontent_createchapter', 'contextcontent');
$escapedButtonLabel = htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');
$buttonMarkup = '<button type="submit" name="submitbutton" class="button primary contextcontent-action-button" aria-label="'.$escapedButtonLabel.'">'
    .'<i data-lucide="save" aria-hidden="true"></i><span>'.$escapedButtonLabel.'</span></button>';
$form->addToForm($buttonMarkup);

if ($mode == 'edit') {
    $hiddeninput = new hiddeninput('id', $id);
    $form->addToForm($hiddeninput->show());

    $hiddeninput = new hiddeninput('chaptercontentid', $chapter['id']);
    $form->addToForm($hiddeninput->show());

    $hiddeninput = new hiddeninput('contextchapterid', $chapter['contextchapterid']);
    $form->addToForm($hiddeninput->show());

}

echo '<div class="addchapterform">' . $header->show() . $form->show() . "</div>";
$chapterlisturl = $this->uri(array('action'=>'chapterlistastree','contextcode'=>$this->contextCode));
$viewchapterurl = $this->uri(array('action'=>'viewchapter'));
?>
