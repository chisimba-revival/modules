<?php
$ret = "";

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('link', 'htmlelements');
$this->loadClass('fieldset', 'htmlelements');
$objIconService = $this->getObject('iconservice', 'ui');
$objWashout = $this->getObject('washout', 'utilities');
$editIcon = $objIconService->render('pencil', array('decorative' => TRUE));
$deleteIcon = $objIconService->render('trash-2', array('decorative' => TRUE));

$this->appendArrayVar('headerParams', '<style>
.assignment_main{display:grid;gap:1rem}.assignment-card{background:#fff;border:1px solid #dbe4e7;border-radius:12px;padding:1.25rem;box-shadow:0 8px 24px rgba(15,23,42,.05)}.assignment-card>h1:first-child{margin-top:0}.assignment-card table{width:100%;border-collapse:separate;border-spacing:0 .35rem}.assignment-brief td{padding:.25rem .8rem .25rem 0;vertical-align:top}.assignment-brief td:nth-child(1),.assignment-brief td:nth-child(3){width:21%;min-width:145px}.assignment-brief td:nth-child(2),.assignment-brief td:nth-child(4){width:29%}.assignment-brief strong{display:block;line-height:1.3}.assignment-submit h1,.assignment-submissions h1{margin-top:0}.assignment-upload-form{display:grid;gap:.75rem;max-width:720px}.assignment-upload-form input[type=file]{width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc}.assignment-upload-form button,.assignment-picker button{padding:.65rem 1rem}.assignment-existing{margin-top:1.25rem;padding-top:.5rem}.assignment-existing h2{font-size:1.05rem;margin:0 0 .75rem}.assignment-picker{display:grid;gap:.75rem;max-width:720px}.assignment-picker-actions{display:flex;gap:.65rem;align-items:center;flex-wrap:wrap}.assignment-picker-file{padding:.75rem;border-radius:8px;background:#f2f7f5;color:#234}.assignment-file-types{color:#52606d;font-size:.92rem}.assignment-empty{margin:.25rem 0;color:#52606d}.assignment-submit-error{margin:0 0 1rem;padding:.85rem 1rem;border:1px solid #dc2626;border-radius:8px;background:#fef2f2;color:#991b1b}@media(max-width:760px){.assignment-card{padding:1rem}.assignment-card table,.assignment-card tbody,.assignment-card tr,.assignment-card td{display:block;width:100%!important}.assignment-card tr{margin-bottom:.75rem}.assignment-card td{padding:.2rem 0}.assignment-brief td:nth-child(odd){margin-top:.45rem}.assignment-brief td:nth-child(even){padding-left:.25rem}}
</style>');

$header = new htmlHeading();
$header->str = $assignment['name'];

if ($this->isValid('edit')) {
    $editLink = new link($this->uri(array('action' => 'edit', 'id' => $assignment['id'])));
    $editLink->link = $editIcon;

    $deleteLink = new link($this->uri(array('action' => 'delete', 'id' => $assignment['id'], 'return' => 'view')));
    $deleteLink->link = $deleteIcon;

    $header->str .= ' ' . $editLink->show() . '&nbsp;' . $deleteLink->show();
}

$header->type = 1;

$objDateTime = $this->getObject('dateandtime', 'utilities');
$objTrimStr = $this->getObject('trimstr', 'strings');

$ret .= '<section class="assignment-card assignment-brief">' . $header->show();

$table = $this->newObject('htmltable', 'htmlelements');

$table->startRow();
$table->addCell('<strong>' . $this->objLanguage->languageText('word_description', 'system', 'Description') . '</strong>', 130);
$table->addCell($objWashout->parseText($assignment['description']), NULL, NULL, NULL, NULL, ' colspan="3"');
$table->endRow();

$classification = isset($assignment['assessment_classification']) ? strtolower((string) $assignment['assessment_classification']) : 'summative';
$classificationKey = $classification === 'formative' ? 'mod_assignment_classification_formative' : 'mod_assignment_classification_summative';
$weightText = $assessmentWeight === null
    ? $this->objLanguage->languageText('mod_assignment_notincoursemark', 'assignment')
    : rtrim(rtrim(number_format((float) $assessmentWeight, 3, '.', ''), '0'), '.') . '%';

$table->startRow();
$table->addCell('<strong>' . ucfirst($this->objLanguage->code2Txt('mod_assignment_lecturer', 'assignment', NULL, '[-author-]')) . '</strong>', 150);
$table->addCell($this->objUser->fullName($assignment['userid']));
$table->addCell('<strong>' . $this->objLanguage->languageText('mod_assignment_assessmentclassification', 'assignment') . '</strong>', 190);
$table->addCell($this->objLanguage->languageText($classificationKey, 'assignment'));
$table->endRow();

$table->startRow();
$table->addCell('<strong>' . $this->objLanguage->languageText('mod_assignment_openingdate', 'assignment', 'Opening Date') . '</strong>', 150);
$table->addCell($objDateTime->formatDate($assignment['opening_date']));
$table->addCell('<strong>' . $this->objLanguage->languageText('mod_assignment_coursemarkweight', 'assignment') . '</strong>', 190);
$table->addCell($weightText);
$table->endRow();

$table->startRow();
$table->addCell('<strong>' . $this->objLanguage->languageText('mod_assignment_closingdate', 'assignment', 'Closing Date') . '</strong>', 130);
$table->addCell($objDateTime->formatDate($assignment['closing_date']));
$table->addCell('<strong>' . $this->objLanguage->languageText('mod_assignment_assignmenttype', 'assignment', 'Assignment Type') . '</strong>', 130);
if ($assignment['format'] == '0') {
    $table->addCell($this->objLanguage->languageText('mod_assignment_online', 'assignment', 'Online'));
} else {
    $table->addCell($this->objLanguage->languageText('mod_assignment_upload', 'assignment', 'Upload'));
}
$table->endRow();

if ($assignment['format'] == '1') {
    $table->startRow();
    $filetypes = $this->objAssignmentUploadablefiletypes->getFiletypes($assignment['id']);
    if (empty($filetypes)) {
        $objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
        $allowedFilesString = $objSysConfig->getValue('FILETYPES_ALLOWED', 'assignment');
        $allowedFileTypes = explode(',', $allowedFilesString);
    } else {
        $allowedFileTypes = array();
        foreach ($filetypes as $filetype) {
            $allowedFileTypes[] = $filetype['filetype'];
        }
    }
    if (empty($allowedFileTypes)) {
        $str = $this->objLanguage->languageText('word_none', 'assignment');
    } else {
        $str = '';
        $separator = '';
        foreach ($allowedFileTypes as $filetype) {
            $str .= $separator . $filetype;
            $separator = '&nbsp;';
        }
    }
    $table->addCell('<strong>' . $this->objLanguage->languageText('mod_assignment_uploadablefiletypes', 'assignment') . '</strong>&nbsp;' . $str, NULL, NULL, NULL, NULL, 'colspan="4"');
    $table->endRow();

    $table->startRow();
    $table->addCell('<br/>', NULL, NULL, NULL, NULL, 'colspan="2"');
    $table->endRow();
}
if ($assignment['usegoals'] == '1') {

    $fieldset = new fieldset();
    $fieldset->setLegend('<b>' . $this->objLanguage->languageText('mod_assignment_learningoutcomes', 'assignment', 'Learning outcomes') . ':</b>');
    $fieldset->addContent($goals);

    $table->startRow();
    $table->addCell($fieldset->show(), NULL, NULL, NULL, NULL, 'colspan="4"');
    $table->endRow();
}
if ($assignment['usegroups'] == '1') {

    $gfieldset = new fieldset();
    $gfieldset->setLegend('<b>'.$this->objLanguage->languageText('mod_assignment_groups', 'assignment', 'Groups').'</b>');
    $gfieldset->addContent($groups);

    $table->startRow();
    $table->addCell($gfieldset->show(), NULL, NULL, NULL, NULL, 'colspan="4"');
    $table->endRow();
}
$ret .= $table->show() . '</section>';
$submittedRet = '';
$submitRet = '';
$knownSubmissionErrors = array('file_required', 'fileforbidden', 'invalidfiletype', 'unabletocopysubmission', 'unabletoupload', 'filedoesnotexist', 'unabletosave', 'alreadysubmitted');
if ($submissionError !== '' && in_array($submissionError, $knownSubmissionErrors, true)) {
    $ret .= '<div class="assignment-submit-error">'
        . $this->objLanguage->languageText('mod_assignment_error_' . $submissionError, 'assignment')
        . '</div>';
}

$htmlHeader = new htmlHeading();
$htmlHeader->type = 1;
$htmlHeader->str = $this->objLanguage->languageText('mod_assignment_submittedassignments', 'assignment', 'Submitted Assignments');
$submittedRet .= $htmlHeader->show();

// If Lecturer, show list of assignments
if ($this->isValid('markassignments')) {
    //$submissions = $this->objAssignmentSubmit->getStudentSubmissions($assignment['id']);
    $table = $this->newObject('htmltable', 'htmlelements');
    $table->startHeaderRow();
    $table->addHeaderCell(ucfirst($this->objLanguage->code2Txt('mod_assignment_studname', 'assignment', NULL, '[-readonly-] name')));
    $table->addHeaderCell($this->objLanguage->languageText('mod_assignment_datesubmitted', 'assignment', 'Date Submitted'));
    $table->addHeaderCell($this->objLanguage->languageText('mod_assignment_mark', 'assignment', 'Mark'));
    $table->addHeaderCell($this->objLanguage->languageText('mod_assignment_comment', 'assignment', 'Comment'));
    $table->endHeaderRow();

    if ((is_countable($submissions) ? count($submissions) : 0) == 0) {
        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_assignment_noassignmentssubmitted', 'assignment', 'No Assignments Submitted Yet'), NULL, NULL, NULL, 'noRecordsMessage', ' colspan="4"');
        $table->endRow();
    } else {

        foreach ($submissions as $submission) {
            $table->startRow();

            $link = new link($this->uri(array('action' => 'viewsubmission', 'id' => $submission['id'])));
            $link->link = $this->objUser->fullName($submission['userid']);

            $table->addCell($link->show());
            $table->addCell($objDateTime->formatDate($submission['datesubmitted']));

            if ($submission['mark'] == NULL) {
                $table->addCell('<em>' . $this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked') . '</em>');
                $table->addCell('<em>' . $this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked') . '</em>');
            } else {
                $table->addCell((float) $assignment['mark'] > 0 ? round(((float) $submission['mark'] / (float) $assignment['mark']) * 100, 2) . '%' : '0%');
                $table->addCell($objTrimStr->strTrim($submission['commentinfo'], 50));
            }

            $table->endRow();
        }
    }

    $submittedRet .= $table->show();

} else {
    // Show Student Views

    //$submissions = $this->objAssignmentSubmit->getStudentAssignment($this->objUser->userId(), $assignment['id']);

//    if ((is_countable($submissions) ? count($submissions) : 0) == 0) {

//    } else if ((is_countable($submissions) ? count($submissions) : 0) == 0) {

//    } else {

    if ((is_countable($submissions) ? count($submissions) : 0) != 0) {

        $table = $this->newObject('htmltable', 'htmlelements');
        $table->startHeaderRow();
        $table->addHeaderCell($this->objLanguage->languageText('mod_assignment_submissions', 'assignment', 'Submissions'));
        $table->addHeaderCell($this->objLanguage->languageText('mod_assignment_datesubmitted', 'assignment', 'Date Submitted'));
        $table->addHeaderCell($this->objLanguage->languageText('mod_assignment_mark', 'assignment', 'Mark'));
        //$table->addHeaderCell($this->objLanguage->languageText('mod_assignment_comment', 'assignment', 'Comment'));
        $table->endHeaderRow();

        $objFile = $this->getObject('dbfile', 'filemanager');
        /*
         * Creating the link to view assignment results
         */
        foreach ($submissions as $submission) {

            $isMarked = date('Y-m-d H:i:s') > $assignment['closing_date'];

            $table->startRow();
            /*
              if (!$isMarked) {
              $table->addCell('<em>'.$this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked').'</em>');
              }
              else {
             */
            $link = new link($this->uri(array('action' => 'viewsubmission', 'id' => $submission['id'])));
            $link->link = $this->objLanguage->languageText('mod_assignment_viewscoremark', 'assignment');
            $table->addCell($link->show());
            /*
              }
             */

            $table->addCell($objDateTime->formatDate($submission['datesubmitted']));

            if (!$isMarked) {
                $table->addCell('<em>' . $this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked') . '</em>');
                //$table->addCell('<em>'.$this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked').'</em>');
            } else {

                if ($submission['mark'] == NULL) {
                    $table->addCell('<em>' . $this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked') . '</em>');
                    //$table->addCell('<em>'.$this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked').'</em>');
                } else {
                    $table->addCell((float) $assignment['mark'] > 0 ? round(((float) $submission['mark'] / (float) $assignment['mark']) * 100, 2) . '%' : '0%');
                    /*
                     * The commented line prevents view of comments untill the assignment is opened for viewing the results
                     */
                    // $table->addCell($objTrimStr->strTrim($submission['commentinfo'], 50));
                }
            }

            $table->endRow();
        }

        $submittedRet .= $table->show();
    }

    if (!$this->isValid('markassignments') && (is_countable($submissions) ? count($submissions) : 0) === 0) {
        $submittedRet .= '<p class="assignment-empty">' . $this->objLanguage->languageText('mod_assignment_nosubmissionsyet', 'assignment') . '</p>';
    }

    if ($this->objAssignmentSubmit->checkOkToSubmit($this->objUser->userId(), $assignment['id'])) {
        $hiddenInput = new hiddeninput('id', $assignment['id']);

        $header = new htmlHeading();
        $header->type = 1;
        $header->str = $this->objLanguage->languageText('mod_assignment_submitassignment', 'assignment', 'Submit Assignment');
        $submitRet .= $header->show();

        // Display by Assignment Type
        $this->objCond = $this->newObject('contextCondition', 'contextpermissions');
        if ($assignment['closing_date'] < date('Y-m-d H:i')) {
            $submitRet .= '<div class="noRecordsMessage">' . $this->objLanguage->languageText('mod_assignment_assignmentclosed', 'assignment', 'Assignment Closed') . '</div>';
        } else if (!($this->objCond->isContextMember('Students'))) { 
            $submitRet .= '<div class="noRecordsMessage">' . $this->objLanguage->languageText('mod_assignment_notstudent', 'assignment', 'Not a Student') . '</div>';

        } else if ($assignment['format'] == '0') { // Online Assignment
            $form = new form('addassignment', $this->uri(array('action' => 'submitonlineassignment')));

            $htmlArea = $this->newObject('htmlarea', 'htmlelements');
            $htmlArea->name = 'text';
            $htmlArea->width = '100%';

            $button = new button('submitform', $this->objLanguage->languageText('mod_assignment_submitassignment', 'assignment', 'Submit Assignment'));
            $button->setToSubmit();
            ;

            $form->addToForm($hiddenInput->show() . $htmlArea->show() . '<br />' . $button->show());

            $submitRet .= $form->show();
        } else { // Upload Assignment
            $pickerUrl = html_entity_decode($this->uri(array('action' => 'filepicker', 'policy' => 'assignment', 'target' => 'assignment_submission_file'), 'filemanager', '', '', false, true), ENT_QUOTES, 'UTF-8');
            $uploadLabel = $this->objLanguage->languageText('mod_assignment_uploaddirect', 'assignment');
            $uploadSubmitLabel = $this->objLanguage->languageText('mod_assignment_uploadandsubmit', 'assignment');
            $chooseLabel = $this->objLanguage->languageText('mod_assignment_chooseexisting', 'assignment');
            $noFileLabel = $this->objLanguage->languageText('mod_assignment_nofileselected', 'assignment');
            $submitLabel = $this->objLanguage->languageText('mod_assignment_submitassignment', 'assignment');
            $wrongTypeLabel = $this->objLanguage->languageText('mod_assignment_filetypenotallowed', 'assignment');
            $allowed = array_values(array_map(function ($type) { return strtolower(ltrim(trim((string) $type), '.')); }, $allowedFileTypes));
            $accept = '.' . implode(',.', $allowed);
            $allowedJson = json_encode($allowed, JSON_UNESCAPED_SLASHES);
            $submitRet .= '<form class="assignment-upload-form" method="post" enctype="multipart/form-data" action="' . htmlspecialchars(str_replace('&amp;', '&', html_entity_decode($this->uri(array('action' => 'uploadassignment')), ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8') . '">'
                . $hiddenInput->show()
                . '<label for="assignment_direct_file"><strong>' . htmlspecialchars($uploadLabel, ENT_QUOTES, 'UTF-8') . '</strong></label>'
                . '<input id="assignment_direct_file" name="file" type="file" accept="' . htmlspecialchars($accept, ENT_QUOTES, 'UTF-8') . '" required>'
                . '<div class="assignment-file-types">' . $this->objLanguage->languageText('mod_assignment_uploadablefiletypes', 'assignment') . ': ' . htmlspecialchars(implode(', ', $allowed), ENT_QUOTES, 'UTF-8') . '</div>'
                . '<div><button type="submit">' . htmlspecialchars($uploadSubmitLabel, ENT_QUOTES, 'UTF-8') . '</button></div></form>';
            $submitRet .= '<div class="assignment-existing"><h2>' . htmlspecialchars($chooseLabel, ENT_QUOTES, 'UTF-8') . '</h2>'
                . '<form class="assignment-picker" method="post" action="' . htmlspecialchars(str_replace('&amp;', '&', html_entity_decode($this->uri(array('action' => 'submitassignment')), ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8') . '">'
                . $hiddenInput->show()
                . '<input type="hidden" id="assignment_file_id" name="assignment_file_id" value="">'
                . '<div id="assignment_file_name" class="assignment-picker-file" aria-live="polite">' . htmlspecialchars($noFileLabel, ENT_QUOTES, 'UTF-8') . '</div>'
                . '<div class="assignment-picker-actions"><button type="button" id="assignment_choose_file">' . htmlspecialchars($chooseLabel, ENT_QUOTES, 'UTF-8') . '</button>'
                . '<button type="submit" id="assignment_submit_file" disabled>' . htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') . '</button></div></form></div>';
            $submitRet .= '<script>(function(){"use strict";var pickerUrl=' . json_encode($pickerUrl) . ',allowed=' . $allowedJson . ',wrongType=' . json_encode($wrongTypeLabel) . ';window.ChisimbaFilePickerReceive=function(target,file){if(target!=="assignment_submission_file"||!file){return;}var ext=String(file.extension||"").toLowerCase();if(allowed.indexOf(ext)===-1){window.alert(wrongType);return;}document.getElementById("assignment_file_id").value=file.id||"";document.getElementById("assignment_file_name").textContent=file.name||"";document.getElementById("assignment_submit_file").disabled=!file.id;};document.getElementById("assignment_choose_file").onclick=function(){window.open(pickerUrl,"chisimba_assignment_picker","width=980,height=720,resizable=yes,scrollbars=yes");};}());</script>';
        }
    }
}

if ($submitRet !== '') {
    $ret .= '<section class="assignment-card assignment-submit">' . $submitRet . '</section>';
}
$ret .= '<section class="assignment-card assignment-submissions">' . $submittedRet . '</section>';
$links = '';

$backLink = new link($this->uri(array()));
$backLink->link = $this->objLanguage->languageText('mod_assignment_backtolist', 'assignment', 'Back to List of Assignments');
$links .= '<p class="assignment_link_return">' . $backLink->show() . '</p>';

if ($this->isValid('markassignments')) { //'edit'
    // [[ JOC OK
    if (!empty($submissions)) {
        $exportLink = new link($this->uri(array("action" => "exporttospreadsheet", "assignmentid" => $assignment['id'])));
        $exportLink->link = $this->objLanguage->languageText('mod_assignment_exporttospreadsheet', 'assignment');
        $links .= '<p class="assignment_link_export">' . $exportLink->show() . '</p>';
    }
    // ]] JOC OK
    // [[ JOC OK
    if ($assignment['format'] == '1' && !empty($submissions)) {
        $downloadalllink = new link($this->uri(array("action" => "downloadall", 'id' => $assignment['id'])));
        $downloadalllink->link = $this->objLanguage->languageText('mod_assignment_downloadall', 'assignment');
        $links .= '<p class="assignment_link_dnlall">' . $downloadalllink->show() . '</p>';
    }
    // ]] JOC OK
}
$ret .= $links;
echo "<div class='assignment_main'>$ret</div>";
?>
