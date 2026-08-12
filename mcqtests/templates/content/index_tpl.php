<?php

/**
 * Template for test home page. Lists the current tests.
 * @package mcqtests
 * @param array $data The list of tests.
 */
// set up layout template
$this->setLayoutTemplate('mcqtests_layout_tpl.php');

// set up html elements
$objTable = &$this->loadClass('htmltable', 'htmlelements');
$objIcon = &$this->newObject('geticon', 'htmlelements');
$objLink = &$this->loadClass('link', 'htmlelements');
$objLayer = &$this->loadClass('layer', 'htmlelements');
$objConfirm = &$this->loadClass('confirm', 'utilities');

// set up language items
$heading = $this->objLanguage->languageText('mod_mcqtests_onlinetests', 'mcqtests');
$nameLabel = $this->objLanguage->languageText('mod_mcqtests_wordname', 'mcqtests');
$chapterLabel = $this->objLanguage->languageText('mod_mcqtests_chapter', 'mcqtests');
$statusLabel = $this->objLanguage->languageText('mod_mcqtests_status', 'mcqtests');
$closeLabel = $this->objLanguage->languageText('mod_mcqtests_closingdate', 'mcqtests');
$notestsLabel = $this->objLanguage->languageText('mod_mcqtests_notests', 'mcqtests');
$openLabel = $this->objLanguage->languageText('mod_mcqtests_openforentry', 'mcqtests');
//$notactiveLabel = $this->objLanguage->languageText('mod_mcqtests_notactive');
$confirmLabel = $this->objLanguage->languageText('mod_mcqtests_deletetest', 'mcqtests');
$assignLabel = $objLanguage->languageText('mod_assignmentadmin_name', 'assignmentadmin');
$testLabel = $this->objLanguage->languageText('mod_mcqtests_test', 'mcqtests');
$editLabel = $this->objLanguage->languageText('word_edit') .' '.$testLabel;
$deleteLabel = $this->objLanguage->languageText('word_delete') .' '.$testLabel;
$listLabel = $this->objLanguage->code2Txt('mod_mcqtests_liststudents', 'mcqtests');
$viewLabel = $this->objLanguage->languageText('word_view') .' '.$testLabel;
$addLabel = $this->objLanguage->languageText('mod_mcqtests_addtest', 'mcqtests');
$exportLabel = $this->objLanguage->languageText('mod_mcqtests_export', 'mcqtests');
$assessmentSheetLabel = $this->objLanguage->languageText('mod_mcqtests_assessmentsheet', 'mcqtests');
$addUrl = $this->uri(array(
    'action' => 'addstep'
));
$iconBase = $this->getResourceUri('icons/lucide/', 'ui');
$addIcon = '<img src="'.$iconBase.'circle-plus.svg" width="20" height="20" alt="" aria-hidden="true" />';
if ($this->isValid('add'))
{
        $addOkay=TRUE;
        $objLink = new link($addUrl);
        $objLink->title = $addLabel;
        $objLink->link = $addIcon;
	$heading.= '<span class="mcq-heading-actions">'.$objLink->show().'</span>';
} else {
       $addOkay=FALSE;
}
$this->setVarByRef('heading', $heading);
if (!empty($testId)) {
    $testData = $this->dbTestadmin->getTests('', 'name', $testId);
    $array = array(
        'item' => $testData[0]['name'],
        'date' => $this->formatDate(date('Y-m-d H:i:s'))
    );
    $confirm = $this->objLanguage->code2Txt('mod_mcqtests_emailconfirm', 'mcqtests', $array);
    echo "<font class='confirm'>".$confirm."</font>";
}
$objTable = new htmltable();
$objTable->cssClass = 'mcq-test-list';
$objTable->width = '99%';
$objTable->cellspacing = '2';
$objTable->cellpadding = '5';
$tableHd = array();
$tableHd[] = $nameLabel;
$tableHd[] = $chapterLabel;
$tableHd[] = $statusLabel;
$tableHd[] = $closeLabel;
$tableHd[] = '&nbsp;';
$objTable->addHeader($tableHd, 'heading');
if (!empty($data)) {
    $i = 0;
    foreach($data as $line) {
        $class = (($i++%2) == 0) ? 'odd' : 'even';
        // link to view test and add questions
if ($addOkay==TRUE){
        $objLink = new link($this->uri(array(
            'action' => 'view',
            'id' => $line['id']
        )));
        $objLink->title = $viewLabel;
        $objLink->link = $line['name'];
        $viewLink = $objLink->show();
} else {
        $viewLink=$line['name'];
}
$icons='';
if ($addOkay==TRUE){
        // edit, mark and delete icons
        $objLink = new link($this->uri(array(
            'action' => 'edit',
            'id' => $line['id']
        )));
        $objLink->title = $editLabel;
        $objLink->link = '<img src="'.$iconBase.'square-pen.svg" width="18" height="18" alt="" aria-hidden="true" />';
        $icons = '<span class="mcq-icon-action">'.$objLink->show().'</span>';

        $objConfirm = new confirm();
        $deleteIcon = '<img src="'.$iconBase.'trash-2.svg" width="18" height="18" alt="" aria-hidden="true" />';
        $objConfirm->setConfirm($deleteIcon, $this->uri(array(
            'action' => 'delete',
            'id' => $line['id']
        )) , $confirmLabel.' '.$line['name'].'?');
        $icons.= '<span class="mcq-icon-action">'.$objConfirm->show().'</span>';
        $objLink = new link($this->uri(array(
            'action' => 'liststudents',
            'id' => $line['id']
        )));
        $objLink->title = $listLabel;
        $objLink->link = '<span class="mcq-results-action"><img src="'.$iconBase.'list-checks.svg" width="19" height="19" alt="" aria-hidden="true" /><span class="mcq-results-action-label">'.$this->objLanguage->languageText('mod_mcqtests_testresults', 'mcqtests').'</span></span>';
        $icons.= '<span class="mcq-icon-action mcq-icon-action-results">'.$objLink->show().'</span>';
        // set up export results icon
        $objLink = new link($this->uri(array(
            'action' => 'doexport',
            'testId' => $line['id']
        )));
        $objLink->title = $exportLabel;
        $objLink->link = '<img src="'.$iconBase.'download.svg" width="18" height="18" alt="" aria-hidden="true" />';
        $icons.= '<span class="mcq-icon-action">'.$objLink->show().'</span>';
}
        // set up table rows
        $tableRow = array();
        $tableRow[] = $viewLink;
        $tableRow[] = $line['node'];
        $tableRow[] = $this->objLanguage->languageText('mod_mcqtests_'.$line['status'], 'mcqtests');
        $tableRow[] = $this->formatDate($line['closingdate']);
        $tableRow[] = '<span class="mcq-action-group">'.$icons.'</span>';
        $objTable->addRow($tableRow, $class);
    }
} else {
    $objTable->startRow();
    $objTable->addCell($notestsLabel, '', '', '', 'noRecordsMessage', 'colspan="5"');
    $objTable->endRow();
}
/*
$advanced = new link($this->uri(array('action'=>'home2')));
$advanced->link = $this->objLanguage->languageText('mod_mcqtest_advanced', 'mcqtests');
$advanced->extra  =  "style='color:#000099;'";
echo "<h2>".$advanced->show()."</h2>";
*/
echo $objTable->show();
if ($this->isValid('add'))
{
	$objLink = new link($addUrl);
	$objLink->link = $addLabel;
	$links = $objLink->show();
}else {
	$links = "";
}
$assessmentSheetLink = new link($this->uri(array('action' => 'assessmentSheet'), 'gradebook'));
$assessmentSheetLink->link = $assessmentSheetLabel;
$links.= '<span class="mcq-home-link">'.$assessmentSheetLink->show().'</span>';
// Link to Assignment Management Module if registered
if ($this->assignment) {
    $objLink = new link($this->uri('', 'assignmentadmin'));
    $objLink->title = $assignLabel;
    $objLink->link = $assignLabel;
    $links.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$objLink->show();
}

$objLayer = new layer();
$objLayer->str = '<div class="mcq-home-actions">'.$links.'</div>';
$objLayer->align = 'center';
echo $objLayer->show();
?>
