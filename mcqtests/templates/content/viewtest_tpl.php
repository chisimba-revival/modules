<?php

/**
 * Template for viewing a test and adding more questions.
 * @package mcqtests
 * @param $data The test information.
 * @param $questions The details of the questions on the test.
 */
// set up layout template
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
// Classes used in this module
$objHeading = $this->loadClass('htmlheading', 'htmlelements');
$objTable = $this->loadClass('htmltable', 'htmlelements');
$objLink = $this->loadClass('link', 'htmlelements');
$objLayer = $this->loadClass('layer', 'htmlelements');
$objIcon = $this->newObject('geticon', 'htmlelements');
$objIconService = $this->getObject('iconservice', 'ui');
$objConfirm = $this->loadClass('confirm', 'utilities');
$objMsg = $this->newObject('timeoutmessage', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('radio', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');

// set up language items
$testdetailsLabel = $this->objLanguage->languageText('mod_mcqtests_testdetailsLabel', 'mcqtests');
$addqestionslabel = $this->objLanguage->languageText('mod_mcqtests_addquestions', 'mcqtests');
$head = $objLanguage->languageText('mod_mcqtests_test', 'mcqtests');
$editLabel = $objLanguage->languageText('word_edit');
$chapterLabel = $objLanguage->languageText('mod_mcqtests_chapter', 'mcqtests');
$statusLabel = $objLanguage->languageText('mod_mcqtests_status', 'mcqtests');
$startdateLabel = $objLanguage->languageText('mod_mcqtests_startdate', 'mcqtests');
$dateLabel = $objLanguage->languageText('mod_mcqtests_closingdate', 'mcqtests');
$totalLabel = $objLanguage->languageText('mod_mcqtests_totalmarks', 'mcqtests');
$backLabel = $objLanguage->languageText('mod_mcqtests_name', 'mcqtests') .' '.$objLanguage->languageText('word_home');
$questionsLabel = $objLanguage->languageText('mod_mcqtests_questions', 'mcqtests');
$questionLabel = $objLanguage->languageText('mod_mcqtests_question', 'mcqtests');
$advancedquestionLabel = $objLanguage->languageText('mod_mcqtests_advancedquestion', 'mcqtests');
$markLabel = $objLanguage->languageText('mod_mcqtests_mark', 'mcqtests');
$numansLabel = $objLanguage->languageText('mod_mcqtests_numanswers', 'mcqtests');
$actionLabel = $objLanguage->languageText('mod_mcqtests_actions', 'mcqtests');
$lbConfirm = $objLanguage->languageText('mod_mcqtests_deletequestion', 'mcqtests');
$wordquestion = $objLanguage->languageText('mod_mcqtests_deletequestionword', 'mcqtests');

$listLabel = ucwords($objLanguage->code2Txt('mod_mcqtests_liststudents', 'mcqtests', array(
    'readonlys' => 'students'
)));
$editIconLabel = $editLabel.' '.$head;
$deleteLabel = $this->objLanguage->languageText('word_delete') .' '.$wordquestion;
$addLabel = $this->objLanguage->languageText('word_add') .' '.$questionLabel;
$addAdvancedLabel = $this->objLanguage->languageText('word_add') . ' '.$advancedquestionLabel;
$upLabel = $this->objLanguage->languageText('word_up');
$downLabel = $this->objLanguage->languageText('word_down');
$durationLabel = $this->objLanguage->languageText('mod_mcqtests_duration', 'mcqtests');
$hoursLabel = $this->objLanguage->languageText('mod_mcqtests_hours', 'mcqtests');
$minLabel = $this->objLanguage->languageText('mod_mcqtests_minutes', 'mcqtests');
$hourLabel = $this->objLanguage->languageText('mod_mcqtests_hour', 'mcqtests');
$noRecords = $this->objLanguage->languageText('mod_mcqtests_nosetquestions', 'mcqtests');
$testTypeLabel = $this->objLanguage->languageText('mod_mcqtests_testtype', 'mcqtests');
$formativeLabel = $this->objLanguage->languageText('word_formative');
$summativeLabel = $this->objLanguage->languageText('word_summative');
$qSequenceLabel = $this->objLanguage->languageText('mod_mcqtests_questionorder', 'mcqtests');
$aSequenceLabel = $this->objLanguage->languageText('mod_mcqtests_answerorder', 'mcqtests');
$scrambledLabel = $this->objLanguage->languageText('word_scrambled');
$sequentialLabel = $this->objLanguage->languageText('word_sequential');
$computerLabel = $this->objLanguage->languageText('mod_mcqtests_comlab', 'mcqtests');
$anyLabLabel = $this->objLanguage->languageText('mod_mcqtests_labs', 'mcqtests');
$esc = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

//switch between the question descriptions and adding questions
$mode = $this->getParam('mode');

$answers_tab = $this->newObject('tabbedbox', 'htmlelements');

$tabcontent = $this->newObject('tabcontent', 'htmlelements');

// Heading for test
$editUrl = $this->uri(array(
    'action' => 'edit',
    'id' => $data['id']
));
$editLink = $objIcon->getEditIcon($editUrl);
$objIcon->title = $listLabel;
$resultsIcon = '<img src="'.$this->getResourceUri('icons/lucide/list-checks.svg', 'ui').'" width="20" height="20" alt="" aria-hidden="true" />';

$objLink = new link($this->uri(array(
    'action' => 'liststudents',
    'id' => $data['id']
)));
$objLink->link = '<span class="mcq-results-action" style="display:inline-flex;align-items:center;gap:.45rem;white-space:nowrap">'.$resultsIcon.'<span class="mcq-results-action-label">'.$this->objLanguage->languageText('mod_mcqtests_testresults', 'mcqtests').'</span></span>';
$editLink.= '&nbsp;'.$objLink->show();

// Show Heading
$heading = '<span class="mcq-test-heading">'.$head.': '.$data['name'].' '.$editLink.'</span>';
$emptyHeading = '';
$this->setVarByRef('heading', $emptyHeading);
echo '<style type="text/css">.mcq-lecturer-panel{border:1px solid rgba(15,23,42,.14);border-radius:.65rem;background:rgba(248,250,252,.72);padding:1rem 1.15rem;margin:0 0 1rem;box-sizing:border-box}.mcq-lecturer-panel h1,.mcq-lecturer-panel h2{margin-top:0}.mcq-lecturer-questions{background:#fff}.mcq-lecturer-home{margin:1rem 0 0}.mcq-test-heading{display:flex;align-items:center;flex-wrap:wrap;gap:.65rem;margin:0 0 .65rem}.mcq-lecturer-summary .mcq_overview{margin-bottom:0}.mcq-question-heading-row{display:flex;align-items:center;flex-wrap:wrap;gap:.75rem;margin:0 0 .75rem}.mcq-question-heading-row h3{margin:0}.mcq-question-actions{margin:0}</style>';

// Create Table for the test information
$objTable = new htmltable();
$objTable->cellpadding = '5';
$objTable->cellspacing = '0';
$objTable->width = '99%';
$objTable->cssClass="mcq_overview";

// Add Activity Status and percentage of mark
$objTable->startRow();
$objTable->addCell('<b>'.$statusLabel.'</b>: '.$objLanguage->languageText('mod_mcqtests_'.$data['status'], 'mcqtests'));
$assessmentSheetLink = new link($this->uri(array('action' => 'assessmentSheet'), 'gradebook'));
$assessmentSheetLink->link = $this->objLanguage->languageText('mod_mcqtests_assessmentsheet', 'mcqtests');
$objTable->addCell($assessmentSheetLink->show());
$objTable->addCell("");
$objTable->endRow();

// Add Start date
$objTable->startRow();
$objTable->addCell('<b>'.$startdateLabel.'</b>: '.$this->objDate->formatDate($data['startdate']));
$objTable->addCell('<b>'.$totalLabel.'</b>: '.$data['totalmark']);
if ($data['timed']) {
    $duration = (0) .'&nbsp;'.$hoursLabel;
    if ($data['duration'] > 0) {
        $hours = floor($data['duration']/60);
        $mins = $data['duration']%60;
        if ($hours == 1) {
            $hoursLabel = $hourLabel;
        }
        $duration = $hours.'&nbsp;'.$hoursLabel.'&nbsp;&nbsp;';
        $duration.= $mins.'&nbsp;'.$minLabel;
    }
    $objTable->addCell('<b>'.$durationLabel.'</b>: '.$duration);
} else {
    $objTable->addCell("");
}
$objTable->endRow();

// Add Cosing date
$objTable->startRow();
$objTable->addCell('<b>'.$dateLabel.'</b>: ' 
  . $this->objDate->formatDate($data['closingdate']));
$objTable->addCell("");
$objTable->addCell("");
$objTable->endRow();

// Add test type
if (isset($data['testtype']) && !empty($data['testtype'])) {
    $testType = $data['testtype'];
} else {
    $testType = $formativeLabel;
}
$objTable->startRow();
$objTable->addCell("<b>".$testTypeLabel.": </b>".$testType);
$objTable->addCell("");
$objTable->addCell("");
$objTable->endRow();

// Add question sequence
if (isset($data['qsequence']) && !empty($data['qsequence'])) {
    $qSequence = $data['qsequence'];
} else {
    $qSequence = $sequentialLabel;
}
$objTable->addRow(array(
    "<b>".$qSequenceLabel.": </b>".$qSequence, "", ""
));

// Add answer sequence
if (isset($data['asequence']) && !empty($data['asequence'])) {
    $aSequence = $data['asequence'];
} else {
    $aSequence = $sequentialLabel;
}
$objTable->addRow(array(
    "<b>".$aSequenceLabel.": </b>".$aSequence, "","")
);

// add computer lab
if (isset($data['comlab']) && !empty($data['comlab'])) {
    $comLab = $data['comlab'];
} else {
    $comLab = $anyLabLabel;
}
$objTable->addRow(array(
    "<b>".$computerLabel.": </b>".$comLab, "", ""
));

// Description
$objTable->startRow();
$objTable->addCell($data['description'], NULL, "top", NULL, NULL, ' colspan="3"');
$objTable->endRow();

// Show Table
echo '<section class="mcq-lecturer-panel mcq-lecturer-summary">'.$heading.$objTable->show().'</section>';

$questionEditingLocked = isset($data['status']) && $data['status'] === 'open';
$count = (is_countable($questions) ? count($questions) : 0);
if (empty($questions)) {
    $count = 0;
}

// Question authoring actions use the same semantic button contract and shared
// icon service so every maintained skin controls their presentation equally.
$addQUrl = $this->uri(array(
    'action' => 'choosequestiontype',
    'id' => $data['id'],
    'count' => $count
));
$addIcon = $objIconService->render('plus', array(
    'decorative' => true,
    'class' => 'chisimba-action-icon'
));
$addQ = $questionEditingLocked ? '' : '<a class="button chisimba-button-secondary" href="'.$addQUrl.'">'.$addIcon.'<span>'.$esc($addLabel).'</span></a>';

$aiGenerate = '';
$aiAvailable = false;
try {
    $objModules = $this->getObject('modules', 'modulecatalogue');
    if ($objModules->checkIfRegistered('ai')) {
        $objAiService = $this->getObject('aiservice', 'ai');
        $aiAvailable = method_exists($objAiService, 'isAvailable') && $objAiService->isAvailable();
    }
} catch (Throwable $exception) {
    $aiAvailable = false;
}
if ($aiAvailable && !$questionEditingLocked) {
    $aiGenerateLabel = $this->objLanguage->languageText('mod_mcqtests_ai_generate_link', 'mcqtests');
    $aiGenerateUrl = $this->uri(array(
        'action' => 'aigenerate',
        'id' => $data['id']
    ));
    $aiIcon = $objIconService->render('sparkles', array(
        'decorative' => true,
        'class' => 'chisimba-action-icon'
    ));
    $aiGenerate = '<a class="button chisimba-button-secondary" href="'.$aiGenerateUrl.'">'.$aiIcon.'<span>'.$esc($aiGenerateLabel).'</span></a>';
}

$str = null;

// Questions Header
$objHeading = new htmlheading();
$objHeading->type = 3;
$objHeading->str = $questionsLabel.' ('.$count.'):';
$qHeading = $objHeading->show();
$str.= '<div class="mcq-question-heading-row">'.$qHeading;
if (!$questionEditingLocked) {
    $str.= '<div class="chisimba-form-actions mcq-question-actions">'.$addQ.$aiGenerate.'</div>';
}
$str.= '</div>';
if ($questionEditingLocked) {
    $str.= '<p class="mcq-edit-lock-notice" style="margin:.25rem 0 1rem;color:#555">'.$this->objLanguage->languageText('mod_mcqtests_activeeditdisabled', 'mcqtests').'</p>';
}

// Confirmation message on saving a question
$confirm = $this->getParam('confirm');
if ($confirm == 'yes') {
    $msg = $this->getSession('confirm');
    $this->unsetSession('confirm');
    $objMsg->setMessage($msg.'&nbsp;&nbsp;'.date('d/m/Y H:i'));
    $str.= '<p>'.$objMsg->show() .'</p>';
}

// Create a New table for the questions
$objTable = new htmltable();
$objTable->cellpadding = 0;
$objTable->cellspacing = 0;
$objTable->width = '99%';
$objTable->cssClass="mcq_questions";
$objTable->startRow();
$objTable->addCell('', '1%', '', '', 'heading');
$objTable->addCell($questionLabel, '', '', '', 'heading');
$objTable->addCell($markLabel, '', '4%', '', 'heading');
$objTable->addCell($actionLabel, '', '8%', '', 'heading', 'colspan="2"');
$objTable->endRow();

// Add questions to table
if (!empty($questions)) {
    $i = 0;
    foreach($questions AS $line) {
        $class = (($i++%2) == 0) ? "odd" : "even";
        if ($questionEditingLocked) {
            $iconsUD = '';
            $icons = '';
            $editUrl = '';
        }
        if ($i > 1) {
            $objIcon->title = $upLabel;
            $url = $this->uri(array(
                'action' => 'questionup',
                'questionId' => $line['id'],
                'id' => $data['id']
            ));
            $iconsUD = $objIcon->getLinkedIcon($url, 'mvup') .'&nbsp;';
        } else {
            $iconsUD = '&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        if ($i < $count) {
            $objIcon->title = $downLabel;
            $url = $this->uri(array(
                'action' => 'questiondown',
                'questionId' => $line['id'],
                'id' => $data['id']
            ));
            $iconsUD.= $objIcon->getLinkedIcon($url, 'mvdown');
        }

        $objIcon->title = $editIconLabel;
        $editUrl = $this->uri(array(
            'action' => 'editquestion',
            'questionId' => $line['id']
        ));
        $icons = $objIcon->getEditIcon($editUrl);
        $objIcon->title = $deleteLabel;
        $deleteIcon = '<img src="'.$this->getResourceUri('icons/lucide/trash-2.svg', 'ui').'" width="18" height="18" alt="" aria-hidden="true" />';
        $pos = FALSE;
        $len = strlen($line['question']);
        $conQuestion = $line['question'];
        if ($len > 10) {
            $pos = strpos($line['question'], '<', 10);
        }
        if ($len > 20 && ($pos > 20 || $pos === FALSE)) {
            $pos = strpos($line['question'], ' ', 20);
        }
        $conQuestion = substr($line['question'], 0, $pos) .'...';

        $objConfirm = new confirm();
        $objConfirm->setConfirm($deleteIcon , $this->uri(array(
            'action' => 'deletequestion',
            'questionId' => $line['id'],
            'id' => $data['id'],
            'mark' => $line['mark']
            )) , $lbConfirm);
        $icons.= $objConfirm->show();
        if ($questionEditingLocked) {
            $iconsUD = '';
            $icons = '';
        }
        $pos = FALSE;
        if ($len > 10) {
            $pos = strpos($line['question'], '<br />', 10);
        }
        if ($len > 100 && $pos === FALSE) {
            $pos = strpos($line['question'], ' ', 100);
        }
        if (!($pos === FALSE)) {
            $strQuestion = substr($line['question'], 0, $pos) .'...';
        } else {
            $strQuestion = $line['question'];
        }
        $objLink = new link($editUrl);
        $objLink->link = $strQuestion;

        $objTable->startRow();
        $objTable->addCell($i.'.');
        $objTable->addCell($this->dbQuestions->previewQuestion($line));
        $objTable->addCell($line['mark']);
        $objTable->addCell($iconsUD);
        $objTable->addCell($icons);
        $objTable->endRow();
    }
    $str.= $objTable->show();
} else {
    $str.= '<p align="center" class="noRecordsMessage">'.$noRecords.'</p>';
}
$objLink = new link($addQUrl);
$objLink->link = $addLabel;
$homeLink = $questionEditingLocked ? '<p>' : '<p>'.$objLink->show() .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

$objLink = new link($this->uri(array(
    ''
)));
$objLink->link = $backLabel;
$homeLink.= $objLink->show() .'</p>';

$objLayer = new layer();
$objLayer->cssClass = '';
$objLayer->align = 'center';
$objLayer->str = '';
$back = $objLayer->show();
$str.= $back;

$objHeading = new htmlheading();
$objHeading->type = 1;
$objHeading->str = $addqestionslabel;

echo '<section class="mcq-lecturer-panel mcq-lecturer-questions">';
echo $objHeading->show();
echo $str;
echo '</section>';

echo '<section class="mcq-lecturer-panel mcq-lecturer-activation">';
$objHeading = new htmlheading();
$objHeading->type = 1;
$objLink = new link($this->uri(array(
    ''
)));
$objLink->link = $backLabel;
$mcqHomeNavigation = '<p class="mcq-lecturer-home">'.$objLink->show().'</p>';
$objHeading->str = $this->objLanguage->languageText('mod_mcqtests_activatetest', 'mcqtests', 'Activate Test');

echo $objHeading->show();

$form = new form ('activatetest', $this->uri(array('action'=>'activatetest')));

$radio = new radio ('status');
$radio->addOption('inactive', $this->objLanguage->languageText('mod_mcqtests_inactive', 'mcqtests', 'Not Active'));
$radio->addOption('open', $this->objLanguage->languageText('mod_mcqtests_openforentry', 'mcqtests', 'Open For Entry'));
$radio->setBreakSpace(' - ');

if ($data['status'] == 'open') {
    $radio->setSelected('open');
} else {
    $radio->setSelected('inactive');
}

$hiddeninput = new hiddeninput('id', $data['id']);
$form->addToForm($hiddeninput->show().'<p>'.$radio->show().'</p>');

$updateLabel = $this->objLanguage->languageText('mod_mcqtests_updatestatus', 'mcqtests');
$previewLabel = $this->objLanguage->languageText('mod_mcqtests_preview', 'mcqtests');
$updateIcon = $objIconService->render('circle-check', array('decorative'=>true, 'class'=>'chisimba-action-icon'));
$previewIcon = $objIconService->render('eye', array('decorative'=>true, 'class'=>'chisimba-action-icon'));
$previewUrl = html_entity_decode($this->uri(array(
    'action' => 'previewtest',
    'id' => $data['id'],
    'mode' => 'notoolbar'
)), ENT_QUOTES, 'UTF-8');
$previewOnClick = "window.open('".str_replace("'", "\\'", $previewUrl)."', 'previewtest', 'fullscreen,scrollbars');";
$form->addToForm(
    '<div class="chisimba-form-actions mcq-activation-actions">'
    .'<button class="button" type="submit" name="update">'.$updateIcon.'<span>'.htmlspecialchars($updateLabel, ENT_QUOTES, 'UTF-8').'</span></button>'
    .'<button class="button chisimba-button-secondary" type="button" onclick="'.htmlspecialchars($previewOnClick, ENT_QUOTES, 'UTF-8').'">'.$previewIcon.'<span>'.htmlspecialchars($previewLabel, ENT_QUOTES, 'UTF-8').'</span></button>'
    .'</div>'
);

echo $form->show();
echo '</section>';
echo $mcqHomeNavigation;
?>