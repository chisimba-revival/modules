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
$addIcon = $objIcon->getAddIcon($addUrl);
if ($this->isValid('add'))
{
        $addOkay=TRUE;
	$heading.= '<span class="mcq-heading-actions">'.$addIcon.'</span>';
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
        $objIcon->title = $editLabel;
        $icons = $objIcon->getEditIcon($this->uri(array(
            'action' => 'edit',
            'id' => $line['id']
        )));
        $objIcon->setIcon('delete');
        $objIcon->title = $deleteLabel;

        $objConfirm = new confirm();
        $objConfirm->setConfirm($objIcon->show() , $this->uri(array(
            'action' => 'delete',
            'id' => $line['id']
        )) , $confirmLabel.' '.$line['name'].'?');
        $icons.= '<span class="mcq-icon-action">'.$objConfirm->show().'</span>';
        $objIcon->setIcon('chart-no-axes-column-increasing');
        $objIcon->title = $listLabel;
        $objLink = new link($this->uri(array(
            'action' => 'liststudents',
            'id' => $line['id']
        )));
        $objLink->link = '<span class="mcq-results-action" style="display:inline-flex;align-items:center;gap:.45rem;white-space:nowrap">'.$objIcon->show().'<span class="mcq-results-action-label">'.$this->objLanguage->languageText('mod_mcqtests_testresults', 'mcqtests').'</span></span>';
        $icons.= '<span class="mcq-icon-action mcq-icon-action-results">'.$objLink->show().'</span>';
        // set up export results icon
        $objIcon->title = $exportLabel;
        $exportIcon = $objIcon->getLinkedIcon($this->uri(array(
            'action' => 'doexport',
            'testId' => $line['id']
        )) , 'exportcvs');
        $icons.= '<span class="mcq-icon-action">'.$exportIcon.'</span>';
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
echo '<style type="text/css">.mcq-heading-actions{display:inline-flex;align-items:center;margin-left:.6rem}.mcq-test-list{border-collapse:separate;border-spacing:0 .35rem}.mcq-test-list td,.mcq-test-list th{padding:.7rem .8rem;vertical-align:middle}.mcq-test-list td:last-child{white-space:nowrap}.mcq-action-group{display:inline-flex;align-items:center;gap:.7rem;flex-wrap:wrap}.mcq-icon-action{display:inline-flex;align-items:center;line-height:1}.mcq-icon-action img{display:block}.mcq-icon-action-results a{display:inline-flex;align-items:center;gap:.45rem}.mcq-assessment-sheet-note{display:inline-block;line-height:1.5}</style>';
echo $objTable->show();
if ($this->isValid('add'))
{
	$objLink = new link($addUrl);
	$objLink->link = '<span class="mcq-results-action">'.$addIcon.'<span>'.$addLabel.'</span></span>';
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
