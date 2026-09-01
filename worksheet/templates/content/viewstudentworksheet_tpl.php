<?php

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('textarea', 'htmlelements');
$this->loadClass('link', 'htmlelements');

$header = new htmlheading();
$header->type = 1;
$link = new link ($this->uri(array('action'=>'worksheetinfo', 'id'=>$id)));
$link->link = $worksheet['name'];
$header->str = $this->objLanguage->languageText('mod_worksheet_worksheet', 'worksheet', 'Worksheet').': '.$link->show();

echo '<br />'.$header->show();
echo $this->objWashout->parseText($worksheet['description']);

$objDateTime = $this->getObject('timeanddateservice', 'timeanddate-service');
$table = $this->newObject('htmltable', 'htmlelements');
$table->startRow();
$table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_closingdate', 'worksheet', 'Closing Date').'</strong>: '.$objDateTime->formatDate($worksheet['closing_date']), '55%');
$table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_questions', 'worksheet', 'Questions').'</strong>: '.(is_countable($questions) ? count($questions) : 0), '15%');
$table->addCell('<strong>'.$this->objLanguage->languageText('mod_worksheet_totalmark', 'worksheet', 'Total Mark').'</strong>: '.$worksheet['total_mark'], '15%');
$table->endRow();
echo $table->show();
echo '<hr />';

$header = new htmlheading();
$header->type = 3;
$header->str = $this->objLanguage->languageText('mod_worksheet_result', 'worksheet', 'Result').':';
echo $header->show();

if ($worksheetResult == FALSE) {
    echo '<p>'.$this->objLanguage->languageText('mod_worksheet_result_notcompleted', 'worksheet', 'Worksheet not completed prior to expiry date').' - 0</p>';
} else {
    if ($worksheetResult['mark'] == '-1') {
        echo '<p class="error">'.$this->objLanguage->languageText('mod_worksheet_result_notmarked', 'worksheet', 'Worksheet submitted but not yet marked').'.</p>';
    } else {
        $score = $this->objLanguage->code2Txt('mod_worksheet_result_marked', 'worksheet', NULL, '[-mark-] out of [-total-]');
        $score = str_replace('[-mark-]', $worksheetResult['mark'], $score);
        $score = str_replace('[-total-]', $worksheet['total_mark'], $score);
        echo '<p>'.$score.'</p>';
    }
}

echo '<hr />';

$reopenForm = new form('reopenstudentworksheet', $this->uri(array('action'=>'reopenstudentworksheet')));
$reopenForm->addToForm((new hiddeninput('id', $worksheetResult['id']))->show());
$reopenForm->addToForm((new hiddeninput('csrf_token', $worksheetReopenToken))->show());
$reopenButton = new button('reopen', $this->objLanguage->languageText('mod_worksheet_reopensubmission', 'worksheet', 'Reopen for student'));
$reopenButton->setToSubmit();
$reopenForm->addToForm('<div class="worksheet-review-action"><p>'.$this->objLanguage->languageText('mod_worksheet_reopensubmission_help', 'worksheet', 'Allow the student to revise and submit this formative worksheet again. Existing answers will be retained.').'</p><div class="chisimba-form-actions">'.$reopenButton->show().'</div></div>');
echo $reopenForm->show();

if (!empty($aiMarkingAvailable) && !empty($aiRecoveryJob)) {
    $recoverLink = new link($this->uri(array('action'=>'aimarkingjob', 'id'=>$aiRecoveryJob['id'])));
    $recoverLink->link = $this->objLanguage->languageText('mod_worksheet_ai_recover', 'worksheet', 'Recover AI suggestions');
    $recoverLink->cssClass = 'button chisimba-button-secondary';
    echo '<div class="worksheet-review-action"><p>'
        .$this->objLanguage->languageText('mod_worksheet_ai_recover_help', 'worksheet', 'The original AI draft has been retained. Recovering it will replace the values shown in this form, but will not change saved marks until you choose Update marks.')
        .'</p><div class="chisimba-form-actions worksheet-review-recover">'.$recoverLink->show().'</div></div>';
} elseif (!empty($aiMarkingAvailable) && empty($aiSuggestionsApplied)) {
    $aiForm = new form('aiassistmark', $this->uri(array('action'=>'aiassistmark')));
    $aiForm->addToForm((new hiddeninput('id', $worksheetResult['id']))->show());
    $aiForm->addToForm((new hiddeninput('csrf_token', $aiMarkingToken))->show());
    $aiButton = new button('aiassist', $this->objLanguage->languageText('mod_worksheet_ai_suggest', 'worksheet', 'Suggest marks with AI'));
    $aiButton->setToSubmit();
    $aiForm->addToForm('<div class="worksheet-review-action"><p>'.$this->objLanguage->languageText('mod_worksheet_ai_explanation', 'worksheet', 'AI can draft marks and feedback for your review. Nothing is saved until you choose Save marks.').'</p><div class="chisimba-form-actions">'.$aiButton->show().'</div></div>');
    echo $aiForm->show();
}
if (!empty($aiSuggestionError)) {
    echo '<div class="error">'.$this->objLanguage->languageText('mod_worksheet_ai_failed', 'worksheet', 'AI suggestions could not be generated. You can continue marking manually.').'</div>';
} elseif (!empty($aiSuggestions)) {
    echo '<div class="success">'.$this->objLanguage->languageText('mod_worksheet_ai_ready', 'worksheet', 'AI suggestions are ready for your review. Check and edit them before saving.').'</div>';
}

$form = new form ('savestudentmark', $this->uri(array('action'=>'savestudentmark')));
$hiddenInput = new hiddeninput('worksheet', $id);
$form->addToForm($hiddenInput->show());
$hiddenInput = new hiddeninput('student', $worksheetResult['userid']);
$form->addToForm($hiddenInput->show());
$hiddenInput = new hiddeninput('csrf_token', $worksheetMarkToken);
$form->addToForm($hiddenInput->show());

$renderRubric = function ($rubric) {
    $html = '<div class="worksheet-review-rubric">';
    $html .= '<h3>'.htmlspecialchars($rubric['title'], ENT_QUOTES, 'UTF-8').'</h3>';
    $html .= '<div class="chisimba-table-wrap"><table class="chisimba-table worksheet-rubric-table"><thead><tr>';
    $html .= '<th>'.$this->objLanguage->languageText('mod_worksheet_rubric_criteria', 'worksheet', 'Rubric criteria').'</th>';
    foreach ($rubric['performances'] as $performance) {
        $html .= '<th>'.htmlspecialchars($performance['label'], ENT_QUOTES, 'UTF-8').'</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rubric['criteria'] as $criterion) {
        $html .= '<tr><th>'.htmlspecialchars($criterion['objective'], ENT_QUOTES, 'UTF-8').'</th>';
        foreach ($criterion['levels'] as $level) {
            $html .= '<td>'.htmlspecialchars($level['description'], ENT_QUOTES, 'UTF-8').'</td>';
        }
        $html .= '</tr>';
    }
    return $html.'</tbody></table></div></div>';
};

// A shared worksheet rubric is orientation for the whole review, not repeated question content.
$sharedRubric = null;
$rubricSignatures = array();
foreach ((array) $structuredRubrics as $rubric) {
    $signature = hash('sha256', json_encode($rubric));
    $rubricSignatures[$signature] = $rubric;
}
if (count($rubricSignatures) === 1) {
    $sharedRubric = reset($rubricSignatures);
    $form->addToForm('<section class="worksheet-review-shared-rubric"><h2>'
        .$this->objLanguage->languageText('mod_worksheet_rubric', 'worksheet', 'Rubric')
        .'</h2>'.$renderRubric($sharedRubric).'</section>');
}

$counter = 1;
foreach ($questions as $question)
{
    $str = '<section class="worksheet-review-question">';
    $str .= '<header class="worksheet-review-question__header">';
    $str .= '<h2>'.$this->objLanguage->languageText('mod_worksheet_question', 'worksheet', 'Question').' '.$counter.'</h2>';
    $str .= '<span class="worksheet-review-question__worth">'.$question['question_worth'].' '
        .$this->objLanguage->languageText('mod_worksheet_marks', 'worksheet', 'Marks').'</span>';
    $str .= '</header><div class="worksheet-review-question__prompt">';
    $str .= $this->objWashout->parseText($question['question']);
    $str .= '</div>';

    $studentAnswer = $this->objWorksheetAnswers->getAnswer($question['id'], $worksheetResult['userid']);

    if ($studentAnswer != FALSE) {
        $str .= '<div class="worksheet-review-block worksheet-review-block--response"><h3>'
            .$this->objLanguage->languageText('mod_worksheet_studentanswer', 'worksheet', 'Student answer')
            .'</h3><div class="worksheet-review-prose">'.$studentAnswer['answer'].'</div></div>';
        $str .= '<div class="worksheet-review-block worksheet-review-block--model"><h3>'
            .$this->objLanguage->languageText('mod_worksheet_modelanswer', 'worksheet', 'Model Answer')
            .'</h3><div class="worksheet-review-prose">'.nl2br(htmlentities($question['model_answer'])).'</div></div>';

        if ($sharedRubric === null && isset($structuredRubrics[$question['id']])) {
            $rubric = $structuredRubrics[$question['id']];
            $str .= $renderRubric($rubric);
        }

        $slider = $this->newObject('slider', 'htmlelements');
        $slider->name = $studentAnswer['id'];
        $slider->maxValue = $question['question_worth'];
        if (!empty($aiSuggestions[$studentAnswer['id']])) {
            $slider->value = $aiSuggestions[$studentAnswer['id']]['mark'];
        } elseif ($studentAnswer['mark'] != '') {
            $slider->value = $studentAnswer['mark'];
        }
        $str .= '<div class="worksheet-review-fields"><div class="chisimba-form-field worksheet-review-mark"><label>'
            .$this->objLanguage->languageText('mod_worksheet_mark', 'worksheet', 'Mark')
            .'</label>'.$slider->show().'</div>';
        $textArea = new textarea('comment_'.$studentAnswer['id']);
        $textArea->value = !empty($aiSuggestions[$studentAnswer['id']])
            ? $aiSuggestions[$studentAnswer['id']]['feedback']
            : $studentAnswer['comments'];
        $str .= '<div class="chisimba-form-field worksheet-review-comment"><label>'
            .$this->objLanguage->languageText('mod_worksheet_comment', 'worksheet', 'Comment')
            .'</label>'.$textArea->show().'</div></div>';
    } else {
        $str .= '<div class="noRecordsMessage">'.$this->objLanguage->languageText('mod_worksheet_notanswered', 'worksheet', 'Not answered').'</div>';
    }

    $str .= '</section>';
    $form->addToForm($str);
    $counter++;
}

$markActionLabel = $worksheetResult['mark'] == '-1'
    ? $this->objLanguage->languageText('mod_worksheet_savemarks', 'worksheet', 'Save Marks')
    : $this->objLanguage->languageText('mod_worksheet_updatemarks', 'worksheet', 'Update marks');
$button = new button ('save', $markActionLabel);
$button->setToSubmit();
$form->addToForm('<div class="chisimba-form-actions worksheet-review-save">'.$button->show().'</div>');
echo $form->show();

$link = new link ($this->uri(NULL));
$link->link = $this->objLanguage->languageText('mod_worksheet_backtoworksheets', 'worksheet', 'Back to Worksheets');
$link2 = new link ($this->uri(array('action'=>'worksheetinfo', 'id'=>$id)));
$link2->link =  $this->objLanguage->languageText('mod_worksheet_backtoworksheet', 'worksheet', 'Back to Worksheet').' - '.$worksheet['name'];
echo '<p>'.$link->show().' / '.$link2->show().'</p>';

?>
