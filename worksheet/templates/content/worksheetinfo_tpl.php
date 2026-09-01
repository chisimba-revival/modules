<?php



$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('textarea', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('link', 'htmlelements');

/*
$js = $this->getJavascriptFile('jquery/jquery.form.js', 'htmlelements');
$this->appendArrayVar('headerParams', $js);
$this->appendArrayVar('headerParams', $this->getJavaScriptFile('worksheet.js'));

$script = "jQuery('#form_addquestion').ajaxForm(options);";
$this->appendArrayVar('bodyOnLoad', $script);
*/

$objIcon = $this->newObject('geticon', 'htmlelements');

$header = new htmlheading();
$header->type = 1;
$header->str = $worksheet['name'].' : '.ucfirst($this->objLanguage->languageText('mod_worksheet_information','worksheet'));

$objStepMenu = $this->newObject('stepmenu', 'navigation');
$objStepMenu->addStep($this->objLanguage->languageText('mod_worksheet_worksheetinfo', 'worksheet', 'Worksheet Information'), $this->objLanguage->languageText('mod_worksheet_worksheetinfo_desc', 'worksheet', 'Add Information about the Worksheet'));
$objStepMenu->addStep($this->objLanguage->languageText('mod_worksheet_addquestions', 'worksheet', 'Add Questions'), $this->objLanguage->languageText('mod_worksheet_addquestions_desc', 'worksheet', 'Add Questions and Mark Allocation to the worksheet'), $this->uri(array('action'=>'managequestions', 'id'=>$id)));
$objStepMenu->addStep($this->objLanguage->languageText('mod_worksheet_activateworksheet', 'worksheet', 'Activate Worksheet'), $this->objLanguage->code2Txt('mod_worksheet_activateworksheet_desc', 'worksheet', NULL, 'Allow [-readonlys-] to start answering worksheet'), $this->uri(array('action'=>'activate', 'id'=>$id)));

$objStepMenu->setCurrent(1);

echo '<div class="worksheet-authoring-steps">'.$objStepMenu->show().'</div>';

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

/*
$editLink = new link ($this->uri(array('action'=>'editworksheet', 'id'=>$id)));
$editLink->link = $this->objLanguage->languageText('mod_worksheet_editworksheet', 'worksheet', 'Edit Worksheet');

$deleteLink = new link ($this->uri(array('action'=>'deleteworksheet', 'id'=>$id)));
$deleteLink->link = $this->objLanguage->languageText('mod_worksheet_deleteworksheet', 'worksheet', 'Delete Worksheet');
*/

$header = new htmlheading();
$header->type = 3;

$header->str = 'Student Submissions:';

echo $header->show();

if ((is_countable($worksheetResults) ? count($worksheetResults) : 0) == 0 || $worksheetResults == FALSE) {
    echo '<div class="noRecordsMessage">'.$this->objLanguage->code2Txt('mod_worksheet_notstudentsattempt', 'worksheet', NULL, 'No [-readonlys-] have attempted the worksheet yet').'.</div>';
} else {
    $hasUnmarkedSubmissions = false;
    foreach ($worksheetResults as $result) {
        if ((string) $result['mark'] === '-1') { $hasUnmarkedSubmissions = true; break; }
    }
    if (!empty($aiMarkingAvailable) && $hasUnmarkedSubmissions) {
        $batchForm = new form('aibatchmark', $this->uri(array('action'=>'aibatchmark')));
        $batchForm->addToForm((new hiddeninput('id', $id))->show());
        $batchForm->addToForm((new hiddeninput('csrf_token', $aiBatchMarkingToken))->show());
        $batchButton = new button('aibatchmark', $this->objLanguage->languageText('mod_worksheet_ai_prepare_all', 'worksheet', 'Prepare AI suggestions for all'));
        $batchButton->setToSubmit();
        $batchForm->addToForm($batchButton->show());
        echo '<div class="worksheet-ai-batch-action">'.$batchForm->show().'</div>';
    }
    $table = $this->newObject('htmltable', 'htmlelements');

    $table->startHeaderRow();
        $table->addHeaderCell($this->objLanguage->code2Txt('mod_worksheet_studnumber', 'worksheet', NULL, '[-readonly-] Number'), 200);
        $table->addHeaderCell($this->objLanguage->code2Txt('mod_worksheet_student', 'worksheet', NULL, '[-readonly-]'));
        $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_finalmark', 'worksheet', 'Final Mark'), 100);
        $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_datecompleted', 'worksheet', 'Date Completed'), 200);
        if (!empty($aiMarkingAvailable)) {
            $table->addHeaderCell($this->objLanguage->languageText('mod_worksheet_ai_status', 'worksheet', 'AI suggestions'), 150);
        }
        $table->addHeaderCell($this->objLanguage->languageText('word_view', 'system', 'View'), 100);
    $table->endHeaderRow();

    foreach ($worksheetResults as $result)
    {
        $table->startRow();
            $table->addCell($this->objUser->getStaffNumber($result['userid']));
            $table->addCell($this->objUser->fullName($result['userid']));

            if ($result['mark'] == '-1') {
                $mark = '<span class="error">'.$this->objLanguage->languageText('mod_worksheet_notmarked', 'worksheet', 'Not Marked').'</span>';
            } else {
                $mark = $result['mark'];
            }

            $table->addCell($mark);
            $table->addCell($objDateTime->formatDate($result['last_modified']));

            if (!empty($aiMarkingAvailable)) {
                if ((string) $result['mark'] !== '-1') {
                    $aiStatus = $this->objLanguage->languageText('mod_worksheet_ai_marks_saved', 'worksheet', 'Marks saved');
                } elseif (!isset($aiMarkingJobsByResult[$result['id']])) {
                    $aiStatus = $this->objLanguage->languageText('mod_worksheet_ai_not_prepared', 'worksheet', 'Not prepared');
                } else {
                    $job = $aiMarkingJobsByResult[$result['id']];
                    if ($job['status'] === 'completed') {
                        $readyLink = new link($this->uri(array('action'=>'aimarkingjob', 'id'=>$job['id'])));
                        $readyLink->link = $this->objLanguage->languageText('mod_worksheet_ai_ready_review', 'worksheet', 'Ready for review');
                        $aiStatus = $readyLink->show();
                    } elseif ($job['status'] === 'running') {
                        $aiStatus = $this->objLanguage->languageText('mod_worksheet_ai_preparing', 'worksheet', 'Preparing');
                    } elseif ($job['status'] === 'failed') {
                        $aiStatus = '<span class="error">'.$this->objLanguage->languageText('mod_worksheet_ai_failed_short', 'worksheet', 'Failed').'</span>';
                    } else {
                        $aiStatus = $this->objLanguage->languageText('mod_worksheet_ai_queued', 'worksheet', 'Queued');
                    }
                }
                $table->addCell($aiStatus);
            }

            $link = new link ($this->uri(array('action'=>'viewstudentworksheet', 'id'=>$result['id'])));
            $link->link = $result['mark'] == '-1'
                ? $this->objLanguage->languageText('mod_worksheet_marksubmission', 'worksheet', 'Mark submission')
                : $this->objLanguage->languageText('mod_worksheet_reviewmarks', 'worksheet', 'Review marks');

            $table->addCell($link->show());
        $table->endRow();
    }

    echo $table->show();
}

?>
