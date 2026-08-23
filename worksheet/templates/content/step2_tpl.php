<?php

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('textarea', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('link', 'htmlelements');
$this->loadClass('confirm', 'utilities');

$rubricTitles = array();
foreach ((array) $rubrics as $rubric) {
    if (!empty($rubric['id'])) {
        $rubricTitles[$rubric['id']] = $rubric['title'];
    }
}

$preview = new link($this->uri(array('action'=>'preview', 'id'=>$worksheet['id'])));
$preview->link = $this->objLanguage->languageText('mod_worksheet_preview', 'worksheet', 'Preview');
$iconBase = $this->getResourceUri('icons/lucide/', 'ui');

$header = new htmlheading();
$header->type = 1;
$header->str = $worksheet['name'].' : '.ucfirst($this->objLanguage->languageText('mod_worksheet_questions', 'worksheet')).' (' . $preview->show() . ')';

$objStepMenu = $this->newObject('stepmenu', 'navigation');
$objStepMenu->addStep($this->objLanguage->languageText('mod_worksheet_worksheetinfo', 'worksheet', 'Worksheet Information'), $this->objLanguage->languageText('mod_worksheet_worksheetinfo_desc', 'worksheet', 'Add Information about the Worksheet'), $this->uri(array('action'=>'worksheetinfo', 'id'=>$id)));
$objStepMenu->addStep($this->objLanguage->languageText('mod_worksheet_addquestions', 'worksheet', 'Add Questions'), $this->objLanguage->languageText('mod_worksheet_addquestions_desc', 'worksheet', 'Add Questions and Mark Allocation to the worksheet'));
$objStepMenu->addStep($this->objLanguage->languageText('mod_worksheet_activateworksheet', 'worksheet', 'Activate Worksheet'), $this->objLanguage->code2Txt('mod_worksheet_activateworksheet_desc', 'worksheet', NULL, 'Allow [-readonlys-] to start answering worksheet'), $this->uri(array('action'=>'activate', 'id'=>$id)));
$objStepMenu->setCurrent(2);

echo '<div class="worksheet-authoring-steps">'.$objStepMenu->show().'</div>';
echo '<br />'.$header->show();
if ($worksheet['activity_status'] != 'inactive') {
  echo '<br /><strong>'.$this->objLanguage->languageText('mod_worksheet_editquestionnote','worksheet', 'Note: The worksheet must be inactive before adding/editing and removing questions is allowed.') . '</strong><br />';
}
echo $this->objWashout->parseText($worksheet['description']);

$objDateTime = $this->getObject('dateandtime', 'utilities');
$table = $this->newObject('htmltable', 'htmlelements');
$table->startRow();
$table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_closingdate', 'worksheet', 'Closing Date').'</strong>: '.$objDateTime->formatDate($worksheet['closing_date']), '55%');
$table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_questions', 'worksheet', 'Questions').'</strong>: '.(is_countable($questions) ? count($questions) : 0), '15%');
$table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_totalmark', 'worksheet', 'Total Mark').'</strong>: '.$worksheet['total_mark'], '15%');
$table->endRow();
echo $table->show();
echo '<hr />';

echo '<div id="worksheetquestions">';
if ((is_countable($questions) ? count($questions) : 0) > 0) {
    $counter = 1;
    $editIcon = '<img src="'.$iconBase.'pencil.svg" width="18" height="18" alt="" aria-hidden="true" />';
    $deletephrase = $this->objLanguage->languageText('mod_worksheet_confirmdeletequestion', 'worksheet');

    foreach ($questions as $question)
    {
        echo '<div class="newForumContainer">';
        echo '<div class="newForumTopic">';
        if ($worksheet['activity_status'] == 'inactive') {
            $questionLink = new link($this->uri(array('action'=>'editquestion', 'id'=>$question['id'])));
            $questionLink->link = $editIcon;
            $deleteConfirm = new confirm();
            $deleteConfirm->setConfirm(
                '<img src="'.$iconBase.'trash-2.svg" width="18" height="18" alt="" aria-hidden="true" />',
                $this->uri(array('action'=>'deletequestion', 'question'=>$question['id'], 'worksheet'=>$id)),
                $deletephrase
            );
            echo '<div class="worksheet-question-actions"><span class="worksheet-icon-action">'.$questionLink->show().'</span><span class="worksheet-icon-action">'.$deleteConfirm->show().'</span></div>';
        }
        echo '<strong>'.$this->objLanguage->languageText('mod_worksheet_question', 'worksheet', 'Question').' '.$counter.':</strong><br />';
        echo $this->objWashout->parseText($question['question']);
        echo '<strong>'.$this->objLanguage->languageText('mod_worksheet_marks', 'worksheet', 'Marks').'</strong> ('.$question['question_worth'].')';
        if (!empty($question['rubric_id']) && isset($rubricTitles[$question['rubric_id']])) {
            echo '<br /><strong>'.$this->objLanguage->languageText('mod_worksheet_rubric', 'worksheet', 'Rubric').':</strong> '.htmlspecialchars($rubricTitles[$question['rubric_id']], ENT_QUOTES, 'UTF-8');
        }
        echo '</div>';
        echo '<div class="newForumContent">';
        echo '<strong>'.$this->objLanguage->languageText('mod_worksheet_modelanswer', 'worksheet', 'Model Answer').':</strong><br />';
        echo nl2br($question['model_answer']);
        echo '</div>';
        echo '</div>';
        $counter++;
    }
} else {
    echo '<div class="noRecordsMessage">'.$this->objLanguage->languageText('mod_worksheet_noquestions', 'worksheet', 'There are no questions at present').'</div>';
}
echo '</div>';

if ($worksheet['activity_status'] == 'inactive') {
    $heading = new htmlHeading();
    $heading->type = 3;
    $heading->str = $this->objLanguage->languageText('mod_worksheet_addquestion', 'worksheet', 'Add Question');
    echo $heading->show();

    $form = new form ('addquestion', $this->uri(array('action'=>'savequestion')));
    $table = $this->newObject('htmltable', 'htmlelements');

    $table->startRow();
    $table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_question', 'worksheet', 'Question').'</strong>:', 200);
    $htmlArea = $this->newObject('htmlarea', 'htmlelements');
    $htmlArea->name = 'question';
    $table->addCell($htmlArea->show());
    $table->endRow();

    $table->startRow();
    $table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_modelanswer', 'worksheet', 'Model Answer').'</strong>:');
    $textarea = new textarea('modelanswer');
    $textarea->extra = ' style="width: 100%"';
    $textarea->rows = 10;
    $table->addCell($textarea->show());
    $table->endRow();

    if ($rubricAvailable) {
        $table->startRow();
        $table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_rubric', 'worksheet', 'Rubric').'</strong>:');
        $select = '<select name="rubric_id" id="input_rubric_id">';
        $select .= '<option value="">'.htmlspecialchars($this->objLanguage->languageText('mod_worksheet_no_rubric', 'worksheet', 'No rubric'), ENT_QUOTES, 'UTF-8').'</option>';
        foreach ($rubrics as $rubric) {
            $select .= '<option value="'.htmlspecialchars($rubric['id'], ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($rubric['title'], ENT_QUOTES, 'UTF-8').'</option>';
        }
        $select .= '</select><br /><small>'.$this->objLanguage->languageText('mod_worksheet_rubric_help', 'worksheet', 'Optional reusable marking rubric').'</small>';
        $createRubricUrl = $this->uri(array('module'=>'rubric', 'action'=>'createtable', 'type'=>'context', 'returnModule'=>'worksheet', 'returnAction'=>'managequestions', 'returnId'=>$id));
        $manageRubricsUrl = $this->uri(array('module'=>'rubric'));
        $select .= '<br /><span class="worksheet-rubric-actions"><a href="'.$createRubricUrl.'">'.$this->objLanguage->languageText('mod_worksheet_create_rubric', 'worksheet', 'Create rubric').'</a> &middot; <a href="'.$manageRubricsUrl.'">'.$this->objLanguage->languageText('mod_worksheet_manage_rubrics', 'worksheet', 'Manage rubrics').'</a></span>';
        $table->addCell($select);
        $table->endRow();
    }

    $table->startRow();
    $table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_questionworth', 'worksheet', 'Question Worth').'</strong>:');
    $textinput = new textinput('mark');
    $table->addCell($textinput->show());
    $table->endRow();

    $table->startRow();
    $table->addCell('&nbsp;');
    $table->addCell('&nbsp;');
    $table->endRow();

    $table->startRow();
    $table->addCell('&nbsp;');
    $button = new button ('savequestion', $this->objLanguage->languageText('mod_worksheet_save', 'worksheet'));
    $button->setToSubmit();
    $table->addCell($button->show());
    $table->endRow();

    $form->addToForm($table->show());
    $hiddenInput = new hiddeninput('worksheet', $worksheet['id']);
    $form->addToForm($hiddenInput->show());
    $form->addRule('mark', $this->objLanguage->languageText('mod_worksheet_validation_mark', 'worksheet', 'Mark should be a number'), 'numeric');
    $form->addRule('mark', $this->objLanguage->languageText('mod_worksheet_validation_mark_req', 'worksheet', 'Please enter a mark'), 'required');
    echo $form->show();
}

echo '<hr />';
$infoLink = new link ($this->uri(array('action'=>'worksheetinfo', 'id'=>$id)));
$infoLink->link = $this->objLanguage->languageText('mod_worksheet_worksheetinfo', 'worksheet', 'Worksheet Information');
$activateLink = new link ($this->uri(array('action'=>'activate', 'id'=>$id)));
$activateLink->link = $this->objLanguage->languageText('mod_worksheet_activatedeactivateworksheet', 'worksheet', 'Activate / Deactivate Worksheet');
$addRemove = $this->objLanguage->languageText('mod_worksheet_addremovequestions', 'worksheet', 'Add / Remove Questions');
echo '<p>'.$infoLink->show().' | '.$addRemove.' | '.$activateLink->show().'</p>';
$link = new link ($this->uri(NULL));
$link->link = $this->objLanguage->languageText('mod_worksheet_backtoworksheets', 'worksheet', 'Back to Worksheets');
echo '<p>'.$link->show().'</p>';

?>
