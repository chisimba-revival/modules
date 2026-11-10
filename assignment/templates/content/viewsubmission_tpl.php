<?php
$ret = "";
$ret .= '<style id="assignment-marking-form-polish">
.assignment_main form[name="_form"],
.assignment_main form#_form {
    box-sizing: border-box;
    margin-top: .8rem;
    max-width: 48rem;
}
.assignment_main form[name="_form"] > table,
.assignment_main form#_form > table {
    border-collapse: separate;
    border-spacing: 0 .55rem;
    width: 100%;
}
.assignment_main form[name="_form"] > table > tbody > tr > td,
.assignment_main form#_form > table > tbody > tr > td {
    padding: .2rem 0;
    vertical-align: top;
}
.assignment_main form[name="_form"] > table > tbody > tr > td:first-child,
.assignment_main form#_form > table > tbody > tr > td:first-child {
    box-sizing: border-box;
    min-width: 10rem;
    padding-right: 1.35rem;
}
.assignment_main form[name="_form"] input[name="mark"],
.assignment_main form#_form input[name="mark"] {
    box-sizing: border-box;
    margin: 0 .5rem 0 0;
    min-height: 2.65rem;
    padding: .4rem .6rem;
    width: 8.5rem;
}
.assignment_main form[name="_form"] textarea[name="commentinfo"],
.assignment_main form#_form textarea[name="commentinfo"] {
    box-sizing: border-box;
    display: block;
    margin: .15rem 0 .25rem;
    min-height: 8rem;
    padding: .7rem .8rem;
    resize: vertical;
    width: min(100%, 38rem);
}
.assignment_main form[name="_form"] input[type="submit"],
.assignment_main form[name="_form"] button[type="submit"],
.assignment_main form#_form input[type="submit"],
.assignment_main form#_form button[type="submit"] {
    background: #0878c9;
    border: 1px solid #0878c9;
    border-radius: .4rem;
    color: #fff;
    cursor: pointer;
    font: inherit;
    font-weight: 600;
    margin-top: .2rem;
    min-height: 2.7rem;
    padding: .55rem 1rem;
}
.assignment_main form[name="_form"] input[type="submit"]:hover,
.assignment_main form[name="_form"] button[type="submit"]:hover,
.assignment_main form#_form input[type="submit"]:hover,
.assignment_main form#_form button[type="submit"]:hover {
    background: #0666ab;
    border-color: #0666ab;
}
.assignment_main .assignment_link_return {
    margin-top: 1.45rem;
}
@media (max-width: 42rem) {
    .assignment_main form[name="_form"] > table > tbody > tr > td,
    .assignment_main form#_form > table > tbody > tr > td {
        display: block;
        width: 100% !important;
    }
    .assignment_main form[name="_form"] > table > tbody > tr > td:first-child,
    .assignment_main form#_form > table > tbody > tr > td:first-child {
        padding: 0 0 .35rem;
    }
}
</style>';

$isLecturerRole = $this->objUser->isCourseAdmin($this->contextCode);
// Load classes
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('link', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('textarea', 'htmlelements');
$this->loadclass('textinput','htmlelements');
$objDateTime = $this->getObject('dateandtime', 'utilities');
$objTrimStr = $this->getObject('trimstr', 'strings');
$objWashout = $this->getObject('washout', 'utilities');
// Section 1
$classification = isset($assignment['assessment_classification'])
    ? strtolower((string) $assignment['assessment_classification']) : 'summative';
$classificationKey = $classification === 'formative'
    ? 'mod_assignment_classification_formative' : 'mod_assignment_classification_summative';
$weightText = $assessmentWeight === null
    ? $this->objLanguage->languageText('mod_assignment_notincoursemark', 'assignment')
    : rtrim(rtrim(number_format((float) $assessmentWeight, 3, '.', ''), '0'), '.') . '%';
$this->appendArrayVar('headerParams', '<style>
.assignment-marking-card{border:1px solid #dbe4e7;border-radius:12px;background:#fff;padding:1.25rem;margin-bottom:1rem}.assignment-marking-card h1{margin-top:0}.assignment-meta{display:grid;grid-template-columns:minmax(145px,190px) 1fr minmax(170px,220px) 1fr;gap:.55rem 1rem}.assignment-meta dt{font-weight:700}.assignment-meta dd{margin:0}.assignment-media{margin:1rem 0;padding:1rem;background:#f3f7f5;border-radius:10px}.assignment-media audio,.assignment-media video{display:block;width:100%;max-width:760px;margin-top:.75rem}.assignment-mark-form{margin-top:1rem}@media(max-width:760px){.assignment-meta{grid-template-columns:1fr}.assignment-meta dt{margin-top:.45rem}}
</style>');
$ret .= '<section class="assignment-marking-card"><h1>' . htmlspecialchars($assignment['name'], ENT_QUOTES, 'UTF-8') . '</h1>'
    . '<dl class="assignment-meta"><dt>' . $this->objLanguage->languageText('word_description', 'system') . '</dt><dd>'
    . $objWashout->parseText($assignment['description']) . '</dd><dt>'
    . ucfirst($this->objLanguage->code2Txt('mod_assignment_lecturer', 'assignment', NULL, '[-author-]')) . '</dt><dd>'
    . htmlspecialchars($this->objUser->fullName($assignment['userid']), ENT_QUOTES, 'UTF-8') . '</dd><dt>'
    . $this->objLanguage->languageText('mod_assignment_totalmark', 'assignment') . '</dt><dd>' . (int) $assignment['mark'] . '</dd><dt>'
    . $this->objLanguage->languageText('mod_assignment_openingdate', 'assignment') . '</dt><dd>' . $objDateTime->formatDate($assignment['opening_date']) . '</dd><dt>'
    . $this->objLanguage->languageText('mod_assignment_assessmentclassification', 'assignment') . '</dt><dd>'
    . $this->objLanguage->languageText($classificationKey, 'assignment') . '</dd><dt>'
    . $this->objLanguage->languageText('mod_assignment_closingdate', 'assignment') . '</dt><dd>' . $objDateTime->formatDate($assignment['closing_date']) . '</dd><dt>'
    . $this->objLanguage->languageText('mod_assignment_coursemarkweight', 'assignment') . '</dt><dd>' . $weightText . '</dd><dt>'
    . $this->objLanguage->languageText('mod_assignment_assignmenttype', 'assignment') . '</dt><dd>'
    . $this->objLanguage->languageText($assignment['format'] == '0' ? 'mod_assignment_online' : 'mod_assignment_upload', 'assignment')
    . '</dd></dl></section>';

// Section 2
$objIcon = $this->getObject('geticon', 'htmlelements');
$objMark = $this->getObject('markimage', 'utilities');
//
$isMarked = $submission['mark'] != NULL && $assignment['closing_date'] < date('Y-m-d H:i:s');
//
if ($assignment['format'] == '1') {
    // Upload
    define('ASSIGNMENT_FT_STUDENT',1);
    define('ASSIGNMENT_FT_LECTURER',2);
    if ($isLecturerRole) {
        $fileType = ASSIGNMENT_FT_STUDENT;
    }
    else {
        if (!$isMarked) {
            $fileType = ASSIGNMENT_FT_STUDENT;
        } else {
            $fileType = ASSIGNMENT_FT_LECTURER;
        }
    }
    switch($fileType){
        case ASSIGNMENT_FT_LECTURER:
            $fileId = $submission['lecturerfileid'];
            break;
        case ASSIGNMENT_FT_STUDENT:
            $fileId = $submission['studentfileid'];
            break;
        default:
            ;
    } // switch
    if (is_null($fileId)) {
        $header = new htmlHeading();
        if ($fileType == ASSIGNMENT_FT_STUDENT) {
            $str = '<em>'.$this->objLanguage->languageText('mod_assignment_noassignmentavailable', 'assignment').'</em>';
        } else if ($fileType == ASSIGNMENT_FT_LECTURER) {
            $str = '<em>'.$this->objLanguage->languageText('mod_assignment_nomarkedassignmentavailable', 'assignment').'</em>';
        } else {
            $str = 'Unkown assignment filetype!';
        }
        $header->str = $str;
        $header->type = 3;
        $ret .= $header->show();
    } else {
        // Header
        $header = new htmlHeading();
        if ($fileType == ASSIGNMENT_FT_STUDENT) {
            $str = $this->objLanguage->code2Txt('mod_assignment_viewassgnby', 'assignment', NULL); //'View ssignment Submitted by [-person-] at [-time-]'
            $str = str_replace('[-person-]', $this->objUser->fullName($submission['userid']), $str);
            $str = str_replace('[-time-]', $objDateTime->formatDate($submission['datesubmitted']), $str);
        } else if ($fileType == ASSIGNMENT_FT_LECTURER) {
            $str = $this->objLanguage->code2Txt('mod_assignment_viewmarkedassignment', 'assignment', NULL); //'View ssignment Submitted by [-person-] at [-time-]'
            $str = str_replace('[-person-]', $this->objUser->fullName($submission['userid']), $str);
            $str = str_replace('[-time-]', $objDateTime->formatDate($submission['datesubmitted']), $str);
        }
        $header->str = $str;
        $header->type = 3;
        $ret .= $header->show();
        // Content
        $objFile = $this->getObject('dbfile', 'filemanager');
        $fileName = $objFile->getFileName($fileId);
        $downloadUrl = str_replace('&amp;', '&', html_entity_decode($this->uri(array('action' => 'downloadfile', 'id' => $submission['id'], 'fileid' => $fileId)), ENT_QUOTES, 'UTF-8'));
        $downloadLink = new link($downloadUrl);
        $downloadLink->link = $this->objLanguage->languageText('word_download', 'system', 'Download');
        $objFileIcon = $this->getObject('fileicons', 'files');
        $ret .= '<div class="assignment-media"><p class="assignment_link_filedn">'
            . $objFileIcon->getFileIcon($fileName) . ' ' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
            . ' &mdash; ' . $downloadLink->show() . '</p>';
        $extension = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
        $mediaUrl = htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8');
        if (in_array($extension, array('mp3', 'wav', 'ogg', 'm4a'), true)) {
            $ret .= '<audio controls preload="metadata" src="' . $mediaUrl . '">'
                . $this->objLanguage->languageText('mod_assignment_playaudio', 'assignment') . '</audio>';
        } elseif (in_array($extension, array('mp4', 'webm'), true)) {
            $ret .= '<video controls preload="metadata" src="' . $mediaUrl . '">'
                . $this->objLanguage->languageText('mod_assignment_playvideo', 'assignment') . '</video>';
        }
        $ret .= '</div>';
    }
    if ($submission['mark'] != NULL && ($assignment['closing_date'] < date('Y-m-d H:i:s') || $this->isValid('edit'))) {
        $header = new htmlHeading();
        $header->str = $this->objLanguage->languageText('mod_assignment_result', 'assignment');
        $header->type = 3;
        $ret .= $header->show();
        $table = $this->newObject('htmltable', 'htmlelements');
        $displayPercentage = (float) $assignment['mark'] > 0 ? round(((float) $submission['mark'] / (float) $assignment['mark']) * 100, 2) : 0;
        $objMark->value = $displayPercentage;
        $table->startRow();
        $table->addCell($objMark->show(), 120);
        $content = '<p><strong>' . $this->objLanguage->languageText('mod_assignment_markpercentage', 'assignment') . ': ' . $displayPercentage . '%</strong></p>';
        $content .= '<p>'.nl2br($submission['commentinfo']).'</p>';
        $table->addCell($content);
        $table->endRow();
        $ret .= $table->show();
    } else {
        $header = new htmlHeading();
        $header->str = '<em>'.$this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked').'</em>';
        $header->type = 3;
        $ret .= $header->show();
    }
    if ($this->isValid('saveuploadmark')) {
        $header = new htmlHeading();
        $header->str = $this->objLanguage->languageText('mod_assignment_markassgn', 'assignment');
        $header->type = 3;
        $ret .= $header->show();
        $markAction = str_replace('&amp;', '&', html_entity_decode($this->uri(array('action' => 'saveuploadmark')), ENT_QUOTES, 'UTF-8'));
        $form = new form('_form', $markAction);
        $hiddenInput = new hiddeninput('id', $submission['id']);
        $currentPercentage = $submission['mark'] !== NULL && (float) $assignment['mark'] > 0
            ? round(((float) $submission['mark'] / (float) $assignment['mark']) * 100, 2) : 0;
        $percentageInput = '<input type="number" name="mark_percentage" min="0" max="100" step="0.01" value="'
            . htmlspecialchars((string) $currentPercentage, ENT_QUOTES, 'UTF-8') . '" required> %';
        $textArea = new textarea('commentinfo');
        $textArea->value = $submission['commentinfo'];
        $button = new button('savemark', $this->objLanguage->languageText('mod_assignment_markassgn', 'assignment'));
        /* CHISIMBA_ASSIGNMENT_NATIVE_MARK_BUTTON */
        $button->sexyButtons = FALSE;
        $button->setCSS('assignment-mark-submit');
        $button->setToSubmit();
        $table = $this->newObject('htmltable', 'htmlelements');
        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_assignment_markpercentage', 'assignment'), 180);
        $table->addCell($percentageInput);
        $table->endRow();
        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_assignment_comment', 'assignment'));
        $table->addCell($textArea->show());
        $table->endRow();
        $table->startRow();
        $table->addCell('&nbsp;');
        $table->addCell($button->show());
        $table->endRow();
        $form->addToForm($hiddenInput->show() . $table->show());
        $ret .= '<div class="assignment-mark-form">' . $form->show() . '</div>';
    }
} else {
    // Online
    // Heading
    $header = new htmlHeading();
    $str = $this->objLanguage->code2Txt('mod_assignment_viewassgnby', 'assignment', NULL); //'View ssignment Submitted by [-person-] at [-time-]'
    $str = str_replace('[-person-]', $this->objUser->fullName($submission['userid']), $str);
    $str = str_replace('[-time-]', $objDateTime->formatDate($submission['datesubmitted']), $str);
    $header->str = $str;
    $header->type = 3;
    $ret .= $header->show();
    // Content
    $ret .= '<div style="border: 1px solid #000; padding: 10px;">'.$submission['online'].'</div>';
    if ($submission['mark'] != NULL  && ($assignment['closing_date'] < date('Y-m-d H:i:s') || $this->isValid('edit'))) {
        // Header
        $header = new htmlHeading();
        $header->str = $this->objLanguage->languageText('mod_assignment_result', 'assignment');
        $header->type = 3;
        $ret .= $header->show();
        // Table
        $table = $this->newObject('htmltable', 'htmlelements');
        $displayPercentage = (float) $assignment['mark'] > 0 ? round(((float) $submission['mark'] / (float) $assignment['mark']) * 100, 2) : 0;
        $objMark->value = $displayPercentage;
        $table->startRow();
        $table->addCell($objMark->show(), 120);
        $content = '<p><strong>' . $this->objLanguage->languageText('mod_assignment_markpercentage', 'assignment') . ': ' . $displayPercentage . '%</strong></p>';
        $content .= '<p>'.nl2br($submission['commentinfo']).'</p>';
        $table->addCell($content);
        $table->endRow();
        $ret .= $table->show();
    } else {
        $header = new htmlHeading();
        $header->str = '<em>'.$this->objLanguage->languageText('mod_assignment_notmarked', 'assignment', 'Not Marked').'</em>';
        $header->type = 3;
        $ret .= $header->show();
    }
    if ($this->isValid('saveonlinemark')) {
        // Header
        $header = new htmlHeading();
        $header->str = $this->objLanguage->languageText('mod_assignment_markassgn', 'assignment', 'Mark Assignment');
        $header->type = 3;
        $ret .= $header->show();
        // Form
        $form = new form ('_form', $this->uri(array('action'=>'saveonlinemark')));
        //$form->extra = 'enctype="multipart/form-data"';
        $hiddenInput = new hiddeninput('id', $submission['id']);
        $textArea = new textarea('commentinfo');
        $textArea->value = $submission['commentinfo'];
        $button = new button('savemark', $this->objLanguage->languageText('mod_assignment_markassgn', 'assignment', 'Mark Assignment'));
        /* CHISIMBA_ASSIGNMENT_NATIVE_MARK_BUTTON */
        $button->sexyButtons = FALSE;
        $button->setCSS('assignment-mark-submit');
        $button->setToSubmit();
    	// Table
    	$table = $this->newObject('htmltable', 'htmlelements');
    	$objSubTable = new htmltable();
    	$objSubTable->width="60%";
    	//Insert mark
    	$objTextinput = new textinput('mark',is_null($submission['mark'])?'0':(int)$submission['mark']);
    	$objTextinput->size='5';
    	$objTextinput->extra=' maxlength=\'4\'';
    	$objSubTable->startRow();
    	$objSubTable->addCell($objTextinput->show().' / '.$assignment['mark']." ".$this->objLanguage->languageText('mod_assignment_typeorslider', 'assignment', 'Mark'),'70%','','left');
    	$objSubTable->addCell("&nbsp;");
    	$objSubTable->endRow();
        $objSlider = $this->newObject('dhtmlgoodies_slider', 'dhtmlgoodies');
        $objSlider->setTargetId('slider_target');
        $objSlider->setFieldRef('document._form.mark');
        $objSlider->setWidth(200);
        $objSlider->setMin(0);
        $objSlider->setmax($assignment['mark']);
     	$objSubTable->startRow();
    	//$objSubTable->addCell("&nbsp;",'70%','','left','',' id=\'slider_target\'');
    	$objSubTable->addCell('<span id=\'slider_target\'></span>'.$objSlider->show(),'70%','','left');
    	$objSubTable->addCell("&nbsp;");
    	$objSubTable->endRow();
        $table->startRow();
        //$table->addCell("&nbsp;");
        $table->addCell($this->objLanguage->languageText('mod_assignment_mark', 'assignment', 'Mark'), 120);
        $table->addCell($objSubTable->show());
        $table->endRow();
    	//Spacer
        $table->startRow();
        $table->addCell("&nbsp;");
        $table->addCell("&nbsp;");
        $table->endRow();
        $table->startRow();
        $table->addCell($this->objLanguage->languageText('mod_assignment_comment', 'assignment', 'Comment'));
        $table->addCell($textArea->show());
        $table->endRow();
        $table->startRow();
        $table->addCell('&nbsp;');
        $table->addCell($button->show());
        $table->endRow();
        $form->addToForm($hiddenInput->show().$table->show());
        $ret .= $form->show();
    }
}
$link = new link($this->uri(array('action'=>'view', 'id'=>$assignment['id'])));
$link->link = $this->objLanguage->languageText('mod_assignment_returntoassgn', 'assignment', 'Return to Assignment');
$ret .= '<p class="assignment_link_return">'. $link->show().'</p>';
echo "<div class='assignment_main'>$ret</div>";
?>