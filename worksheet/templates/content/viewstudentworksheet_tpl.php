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

$objDateTime = $this->getObject('dateandtime', 'utilities');
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
$reopenForm->addToForm('<div class="worksheet-reopen"><p>'.$this->objLanguage->languageText('mod_worksheet_reopensubmission_help', 'worksheet', 'Allow the student to revise and submit this formative worksheet again. Existing answers will be retained.').'</p>'.$reopenButton->show().'</div>');
echo $reopenForm->show();

if (!empty($aiMarkingAvailable)) {
    $aiForm = new form('aiassistmark', $this->uri(array('action'=>'aiassistmark')));
    $aiForm->addToForm((new hiddeninput('id', $worksheetResult['id']))->show());
    $aiForm->addToForm((new hiddeninput('csrf_token', $aiMarkingToken))->show());
    $aiButton = new button('aiassist', $this->objLanguage->languageText('mod_worksheet_ai_suggest', 'worksheet', 'Suggest marks with AI'));
    $aiButton->setToSubmit();
    $aiForm->addToForm('<div class="worksheet-ai-assist"><p>'.$this->objLanguage->languageText('mod_worksheet_ai_explanation', 'worksheet', 'AI can draft marks and feedback for your review. Nothing is saved until you choose Save marks.').'</p>'.$aiButton->show().'</div>');
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

$counter = 1;
foreach ($questions as $question)
{
    $str = '<div class="newForumContainer">';
    $str .= '<div class="newForumTopic">';
    $str .= '<strong>'.$this->objLanguage->languageText('mod_worksheet_question', 'worksheet', 'Question').' '.$counter.':</strong><br />';
    $str .= $this->objWashout->parseText($question['question']);
    $str .= '<strong>'.$this->objLanguage->languageText('mod_worksheet_marks', 'worksheet', 'Marks').'</strong> ('.$question['question_worth'].')';
    $str .= '</div>';
    $str .= '<div class="newForumContent">';

    $studentAnswer = $this->objWorksheetAnswers->getAnswer($question['id'], $worksheetResult['userid']);

    if ($studentAnswer != FALSE) {
        $str .= $studentAnswer['answer'];
        $str .= '</div><div class="newForumContent">';
        $str .= '<p><strong>'.$this->objLanguage->languageText('mod_worksheet_markanswer', 'worksheet', 'Mark Answer').':</strong></p>';

        $table = $this->newObject('htmltable', 'htmlelements');
        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_worksheet_modelanswer', 'worksheet', 'Model Answer').':', 180);
        $table->addCell(nl2br(htmlentities($question['model_answer'])));
        $table->endRow();

        if (isset($structuredRubrics[$question['id']])) {
            $rubric = $structuredRubrics[$question['id']];
            $rubricHtml = '<strong>'.htmlspecialchars($rubric['title'], ENT_QUOTES, 'UTF-8').'</strong>';
            $rubricHtml .= '<table class="table table-bordered worksheet-rubric-table"><thead><tr>';
            $rubricHtml .= '<th>'.$this->objLanguage->languageText('mod_worksheet_rubric_criteria', 'worksheet', 'Rubric criteria').'</th>';
            foreach ($rubric['performances'] as $performance) {
                $rubricHtml .= '<th>'.htmlspecialchars($performance['label'], ENT_QUOTES, 'UTF-8').'</th>';
            }
            $rubricHtml .= '</tr></thead><tbody>';
            foreach ($rubric['criteria'] as $criterion) {
                $rubricHtml .= '<tr><th>'.htmlspecialchars($criterion['objective'], ENT_QUOTES, 'UTF-8').'</th>';
                foreach ($criterion['levels'] as $level) {
                    $rubricHtml .= '<td>'.htmlspecialchars($level['description'], ENT_QUOTES, 'UTF-8').'</td>';
                }
                $rubricHtml .= '</tr>';
            }
            $rubricHtml .= '</tbody></table>';

            $table->startRow();
            $table->addCell($this->objLanguage->languageText('mod_worksheet_rubric', 'worksheet', 'Rubric').':');
            $table->addCell($rubricHtml);
            $table->endRow();
        }

        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_worksheet_mark', 'worksheet', 'Mark').':');
        $slider = $this->newObject('slider', 'htmlelements');
        $slider->name = $studentAnswer['id'];
        $slider->maxValue = $question['question_worth'];
        if (!empty($aiSuggestions[$studentAnswer['id']])) {
            $slider->value = $aiSuggestions[$studentAnswer['id']]['mark'];
        } elseif ($studentAnswer['mark'] != '') {
            $slider->value = $studentAnswer['mark'];
        }
        $table->addCell($slider->show());
        $table->endRow();

        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_worksheet_comment', 'worksheet', 'Comment').':');
        $textArea = new textarea('comment_'.$studentAnswer['id']);
        $textArea->value = !empty($aiSuggestions[$studentAnswer['id']])
            ? $aiSuggestions[$studentAnswer['id']]['feedback']
            : $studentAnswer['comments'];
        $table->addCell($textArea->show());
        $table->endRow();

        $str .= $table->show();
    } else {
        $str .= '<div class="noRecordsMessage">'.$this->objLanguage->languageText('mod_worksheet_notanswered', 'worksheet', 'Not answered').'</div>';
    }

    $str .= '</div>';
    $str .= '</div>';
    $form->addToForm($str);
    $counter++;
}

$button = new button ('save', $this->objLanguage->languageText('mod_worksheet_savemarks', 'worksheet', 'Save Marks'));
$button->setToSubmit();
$form->addToForm('<p align="center">'.$button->show().'</p>');
echo $form->show();

$link = new link ($this->uri(NULL));
$link->link = $this->objLanguage->languageText('mod_worksheet_backtoworksheets', 'worksheet', 'Back to Worksheets');
$link2 = new link ($this->uri(array('action'=>'worksheetinfo', 'id'=>$id)));
$link2->link =  $this->objLanguage->languageText('mod_worksheet_backtoworksheet', 'worksheet', 'Back to Worksheet').' - '.$worksheet['name'];
echo '<p>'.$link->show().' / '.$link2->show().'</p>';

?>
