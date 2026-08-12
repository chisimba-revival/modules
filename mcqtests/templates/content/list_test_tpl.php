<?php
/**
 * Template to display a list of students that have completed a specified test.
 * @package mcqtests
 * @param string $data The list of students
 */
// set up layout template
$this->setLayoutTemplate('mcqtests_layout_tpl.php');

// set up html elements
$objTable = &$this->loadClass('htmltable', 'htmlelements');
$objLink = &$this->loadClass('link', 'htmlelements');
$objLayer = &$this->loadClass('layer', 'htmlelements');
$objConfirm = &$this->loadClass('confirm', 'utilities');

// set up language items
$heading = ucwords($this->objLanguage->code2Txt('mod_mcqtests_liststudents', 'mcqtests', array(
    'readonlys' => 'students'
)));
$exitLabel = $this->objLanguage->languageText('word_exit');
$testLabel = $this->objLanguage->languageText('mod_mcqtests_test', 'mcqtests');
$studentLabel = ucwords($this->objLanguage->languageText('mod_context_readonly', 'mcqtests'));
$markLabel = $this->objLanguage->languageText('mod_mcqtests_mark', 'mcqtests');
$reopenLabel = $this->objLanguage->languageText('mod_mcqtests_reopen', 'mcqtests');
$notStartedLabel = $this->objLanguage->languageText('mod_mcqtests_notstarted', 'mcqtests');
$assignLabel = $this->objLanguage->languageText('mod_assignmentadmin_name', 'assignmentadmin');
$takenLabel = $this->objLanguage->languageText('mod_mcqtests_datetaken', 'mcqtests');
$attemptsLabel = $this->objLanguage->languageText('mod_mcqtests_attempts', 'mcqtests');
$historyLabel = $this->objLanguage->languageText('mod_mcqtests_history', 'mcqtests');
$this->setVarByRef('heading', $heading);
$str = '<font size="3"><b>'.$testLabel.':</b>&nbsp;&nbsp;&nbsp;'.$test['name'].'<p /></font>';

$objTable = new htmltable();
$objTable->cssClass = 'mcq-student-list';
$objTable->cellpadding = 5;
$objTable->cellspacing = 2;
$objTable->width = '99%';
$tableHd = array();
$tableHd[] = $studentLabel;
$tableHd[] = $markLabel.' (%)';
$tableHd[] = $takenLabel;
$tableHd[] = $attemptsLabel;
$tableHd[] = '';
$objTable->addHeader($tableHd, 'heading');

if (!empty($data)) {
    $i = 0;
    foreach($data as $line) {
        $class = (($i++%2) == 0) ? 'even' : 'odd';
        $isNotStarted = isset($line['attemptstatus'])
            && $line['attemptstatus'] === 'notstarted';
        if ($isNotStarted) {
            $mark = '<span class="subdued">'.$notStartedLabel.'</span>';
        } else if ($totalmark != 0) {
            //trigger_error('$line::'.var_export($line, true));
            //trigger_error('isset($line[\'endtime\'])::'.var_export(isset($line['endtime']), true));
            //trigger_error('endtime::'.var_export($line['endtime'], true));
            if (
                intval($line['mark']) == 0
                && is_null($line['endtime'])
            ) {
                $mark = '<span style="color: red;">'.$this->objLanguage->languageText('mod_mcqtests_legacynotcompleted','mcqtests').'</span>';
            } else if (
                intval($line['mark']) == -1
            ) {
                $mark = '<span style="color: red;">'.$this->objLanguage->languageText('mod_mcqtests_notcompleted','mcqtests').'</span>';
            } else {
                $mark = round($line['mark']/$totalmark*100, 2) .'%';
            }
        } else {
            if (intval($line['mark']) == -1) {
                $mark = 'unmarked';
            } else {
                $mark = $line['mark'];
            }
        }
        // Use submission time for completed attempts. Interrupted attempts
        // have no submission time, so show when they began.
        $taken = '';
        if (isset($line['endtime']) && !empty($line['endtime'])) {
            $taken = $this->formatDate($line['endtime']);
        } else if (!$isNotStarted && isset($line['starttime']) && !empty($line['starttime'])) {
            $taken = $this->formatDate($line['starttime']);
        }
        $objConfirm = new confirm();
        if ($isNotStarted) {
            $openLink = '';
        } else if($this->getParam('action') == 'liststudents2') {
            $objConfirm->setConfirm($reopenLabel, $this->uri(array(
                'action' => 'reopen',
                'id' => $test['id'],
                'studentId' => $line['studentid'],
                'testtype' => 'advanced'
            )) , 'reopen?');
        }
        else if (!$isNotStarted) {
            $objConfirm->setConfirm($reopenLabel, $this->uri(array(
                'action' => 'reopen',
                'id' => $test['id'],
                'studentId' => $line['studentid']
            )) , 'reopen?');
        }
        if (!$isNotStarted) {
            $openLink = $objConfirm->show();
        }
        //         $objLink = new link($this->uri(array('action'=>'reopen', 'id'=>$test['id'],
        //             'studentId'=>$line['studentId'])));
        //         $objLink->link = $reopenLabel;
        //         $openLink = $objLink->show();
        $studentName = !empty($line['fullname'])
            ? $line['fullname']
            : $this->objUser->fullname($line['studentid']);
        if ($isNotStarted) {
            $studentLink = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        } else {
            $objLink = new link($this->uri(array(
                'action' => 'showtest',
                'id' => $test['id'],
                'studentId' => $line['studentid']
            )));
            $objLink->link = $studentName;
            $studentLink = $objLink->show();
        }
        $row = array();
        $row[] = $studentLink;
        $row[] = $mark;
        $row[] = $taken;
        $attemptCount = isset($line['attemptcount']) ? (int) $line['attemptcount'] : 0;
        $attemptNumber = isset($line['attemptnumber']) ? (int) $line['attemptnumber'] : 0;
        $attemptInfo = $attemptCount > 0 ? $attemptNumber . ' / ' . $attemptCount : '';
        if ($attemptCount > 0) {
            $historyLink = new link($this->uri(array('action' => 'attempthistory', 'id' => $test['id'], 'studentId' => $line['studentid'])));
            $historyLink->link = $historyLabel . ' (' . $attemptCount . ')';
            $attemptInfo .= '<br />' . $historyLink->show();
        }
        $row[] = $attemptInfo;
        $row[] = $openLink;
        $objTable->addRow($row, $class);
    }
}
$str.= $objTable->show();
if($this->getParam('action') == 'liststudents2') {
    $objLink = new link($this->uri(array(
        'action' => 'view2',
        'id' => $test['id']
    )));
}
else {
    $objLink = new link($this->uri(array(
        'action' => 'view',
        'id' => $test['id']
    )));
}
$objLink->link = $exitLabel;
$testLink = $objLink->show();
// Add link to Assignment Management if its registered
if ($this->assignment) {
    $objLink = new link($this->uri(array(
        'action' => 'viewbyletter',
        'letter' => 'listall'
    ) , 'assignmentadmin'));
    $objLink->link = $assignLabel;
    $testLink.= '&nbsp;&nbsp;&nbsp;&nbsp;'.$objLink->show();
}

$objLayer = new layer();
$objLayer->str = '<p />'.$testLink;
$objLayer->align = 'center';
$str.= $objLayer->show();
echo $str;
?>
