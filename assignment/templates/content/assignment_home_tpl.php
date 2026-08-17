<?php
$ret = "";
$openLabel = $this->objLanguage->languageText('mod_assignment_open', 'assignment');
$closedLabel = $this->objLanguage->languageText('mod_assignment_closed', 'assignment');
$viewLabel = $this->objLanguage->languageText('mod_assignment_view', 'assignment');
$uploadLabel = $this->objLanguage->languageText('mod_assignment_upload', 'assignment');
$onlineLabel = $this->objLanguage->languageText('mod_assignment_online', 'assignment');
$addLabel = $this->objLanguage->languageText('mod_assignment_addassignment', 'assignment', 'Add assignment');
$editLabel = $this->objLanguage->languageText('word_edit', 'system', 'Edit');
$deleteLabel = $this->objLanguage->languageText('word_delete', 'system', 'Delete');

// Set up html elements
$this->loadClass('htmltable', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('link', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$objIconService = $this->getObject('iconservice', 'ui');
$addIcon = $objIconService->render('plus', array('decorative' => TRUE));
$editIcon = $objIconService->render('pencil', array('decorative' => TRUE));
$deleteIcon = $objIconService->render('trash-2', array('decorative' => TRUE));
$assignmentIcon = $objIconService->render('file-text', array('decorative' => TRUE, 'class' => 'assignment-row-icon'));
$submittedListIcon = $objIconService->render('scroll-text', array('decorative' => TRUE, 'class' => 'assignment-row-icon'));
$objTimeOut = $this->newObject('timeoutMessage', 'htmlelements');

$objTrim = $this->getObject('trimstr', 'strings');
$createButton = new button('submit', $this->objLanguage->languageText('mod_assignment_createassignments', 'assignment', 'Create Assignment'));
//$createButton->setToSubmit();

$objHead = new htmlheading();
$objHead->str = $this->objLanguage->languageText('mod_assignment_assignments', 'assignment', 'Assignments');
$objHead->type = 1;

if ($this->isValid('add')) {

    $link = new link($this->uri(array('action' => 'add')));
    $link->title = $addLabel;
    $link->link = $addIcon;
    $objHead->str .= ' ' . $link->show();
}

$ret .= $objHead->show();

$objTable = $this->newObject('htmltable', 'htmlelements');

$objTable->startHeaderRow();
$objTable->addHeaderCell($this->objLanguage->languageText('word_name', 'system', 'Name'), '20%');
$objTable->addHeaderCell($this->objLanguage->languageText('mod_assignment_assignmenttype', 'assignment', 'Assignment Type'), '13%');
//$objTable->addHeaderCell($this->objLanguage->languageText('word_description', 'system', 'Description'));
$objTable->addHeaderCell(ucfirst($this->objLanguage->code2Txt('mod_assignment_lecturer', 'assignment', NULL, '[-author-]')), '15%');
$objTable->addHeaderCell($this->objLanguage->languageText('mod_assignment_closingdate', 'assignment', 'Closing Date'), '15%');
$objTable->addHeaderCell($this->objLanguage->languageText('word_status', 'system', 'Status'), '8%');

if ($this->isValid('edit') && (is_countable($assignments) ? count($assignments) : 0) > 0) {
    $objTable->addHeaderCell('&nbsp;', '60');
}

$objTable->endHeaderRow();

if ((is_countable($assignments) ? count($assignments) : 0) == 0) {



    $objTable->startRow();
    $objTable->addCell($this->objLanguage->languageText('mod_assignment_noassignments', 'assignment', 'No Assignments'), '', '', '', 'noRecordsMessage', 'colspan="6"');
    $objTable->endRow();
} else {

    $i = 0;
    $status = '';

    $counter = 0;


    foreach ($assignments as $assignment) {
        $class = ($i++ % 2 == 0) ? 'odd' : 'even';

        if ($assignment['closing_date'] > date('Y-m-d H:i')) {
            if (($assignment['opening_date'] < date('Y-m-d H:i')) || $assignment['opening_date'] == NULL) {
                $status = $openLabel;
            } else {
                $status = $this->objLanguage->languageText('mod_assignment_notopenforentry', 'assignment', 'Not Open for Entry');
            }
        } else {
            $status = $closedLabel;
        }

        $objLink = new link($this->uri(array('action' => 'view', 'id' => $assignment['id'])));
        $objLink->title = $viewLabel . ' ' . $assignment['name'];
        $objLink->link = $assignmentIcon . '<span>' . htmlspecialchars($assignment['name'], ENT_QUOTES, 'UTF-8') . '</span>';


        // Display whether the assignment is online or uploadable
        if ($assignment['format'] == 1) {
            $format = $uploadLabel;
        } else {
            $format = $onlineLabel;
        }

        $okToShow = FALSE;

        if (($assignment['opening_date'] < date('Y-m-d H:i')) || $assignment['opening_date'] == NULL) {
            $okToShow = TRUE;
        }

        if ($assignment['visibility'] == '0') {
            $okToShow = FALSE;
            $groups = $this->objAssignmentGroups->getWorkgroups($assignment['id']);
            $mc = $this->getObject('modules', 'modulecatalogue');
            if ($mc->checkIfRegistered('workgroup')) {
                $objUser = $this->getObject('user', 'security');
                $objGroups = $this->getObject('dbWorkgroupUsers', 'workgroup');
                foreach ($groups as $group) {

                    if ($objGroups->memberOfWorkGroup($objUser->userid(), $group['workgroup_id'])) {
                        $okToShow = TRUE;
                    }
                }
            }

        }
        if ($this->isValid('edit')) {
            $okToShow = TRUE;
        }

        if ($okToShow) {

            $counter++;

            $objTable->startRow();
            $objTable->addCell($objLink->show(), '20%', '', '', $class);
            $objTable->addCell($format, '13%', '', '', $class);
            //$objTable->addCell($objTrim->strTrim(strip_tags($assignment['description']), 50),'','','',$class);
            $objTable->addCell($this->objUser->fullname($assignment['userid']), '15%', '', '', $class);
            $objTable->addCell($this->objDate->formatDate($assignment['closing_date']), '15%', '', '', $class);
            $objTable->addCell($status, '8%', '', '', $class);

            if ($this->isValid('edit')) {
                $editLink = new link($this->uri(array('action' => 'edit', 'id' => $assignment['id'])));
                $editLink->title = $editLabel . ' ' . $assignment['name'];
                $editLink->link = $editIcon;

                $deleteLink = new link($this->uri(array('action' => 'delete', 'id' => $assignment['id'])));
                $deleteLink->title = $deleteLabel . ' ' . $assignment['name'];
                $deleteLink->link = $deleteIcon;

                $objTable->addCell($editLink->show() . '&nbsp;' . $deleteLink->show(), '60', NULL, NULL, $class);
            }
            $objTable->endRow();
        }
    }

    if ($counter == 0) {
        $objTable->startRow();
        $objTable->addCell($this->objLanguage->languageText('mod_assignment_noassignments', 'assignment', 'No Assignments'), '', '', '', 'noRecordsMessage', 'colspan="6"');
        $objTable->endRow();
    }
}

$ret .= $objTable->show();

if ($this->isValid('markassignments')) {
    $ret .= '<section class="assignment-admin-card"><h2>'
        . $this->objLanguage->languageText('mod_assignment_markingoverview', 'assignment') . '</h2>';
    if (empty($submissionSummary)) {
        $ret .= '<p>' . $this->objLanguage->languageText('mod_assignment_noassignments', 'assignment') . '</p>';
    } else {
        $ret .= '<table class="assignment-summary"><thead><tr><th>'
            . $this->objLanguage->languageText('word_name', 'system') . '</th><th>'
            . $this->objLanguage->languageText('mod_assignment_submittedcount', 'assignment') . '</th><th>'
            . $this->objLanguage->languageText('mod_assignment_markedcount', 'assignment') . '</th><th></th></tr></thead><tbody>';
        foreach ($submissionSummary as $summary) {
            $markUrl = htmlspecialchars(str_replace('&amp;', '&', html_entity_decode($this->uri(array('action' => 'view', 'id' => $summary['assignmentid'])), ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8');
            $ret .= '<tr><td>' . htmlspecialchars($summary['name'], ENT_QUOTES, 'UTF-8') . '</td><td>'
                . (int) $summary['submitted'] . '</td><td>' . (int) $summary['marked']
                . '</td><td><a class="assignment-mark-link" href="' . $markUrl . '">'
                . $this->objLanguage->languageText('mod_assignment_marksubmissions', 'assignment') . '</a></td></tr>';
        }
        $ret .= '</tbody></table>';
    }
    $policyAction = htmlspecialchars(str_replace('&amp;', '&', html_entity_decode($this->uri(array('action' => 'savecoursepolicy')), ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8');
    $options = array('single' => 'mod_assignment_policy_single', 'until_closing' => 'mod_assignment_policy_untilclosing', 'unlimited' => 'mod_assignment_policy_unlimited');
    $ret .= '</section><section class="assignment-admin-card"><h2>'
        . $this->objLanguage->languageText('mod_assignment_submissionpolicy', 'assignment') . '</h2><form method="post" action="' . $policyAction . '">';
    foreach ($options as $value => $key) {
        $checked = $courseSubmissionPolicy === $value ? ' checked' : '';
        $ret .= '<label class="assignment-policy-option"><input type="radio" name="submission_policy" value="'
            . $value . '"' . $checked . '> <span>' . $this->objLanguage->languageText($key, 'assignment') . '</span></label>';
    }
    $ret .= '<button type="submit">' . $this->objLanguage->languageText('mod_assignment_savecoursepolicy', 'assignment')
        . '</button></form></section>';
}


if ($this->isValid('add')) {
    $link = new link($this->uri(array('action' => 'add')));
    $link->link = $this->objLanguage->languageText('mod_assignment_addassignment', 'assignment', 'Add Assignment');

    $ret .= '<p class="assignment_link_add">' . $link->show() . '</p>';
}


if ($this->objUser->isContextStudent($this->contextCode)) {
    $this->objLink->link($this->uri(array('action' => 'displaylist')));
    $this->objLink->link = $submittedListIcon . '<span>' . $this->objLanguage->languageText('mod_assignment_submittedassignmentslist', 'assignment') . '</span>';
    $ret .= '<p class="assignment_link_submittedlist">' . $this->objLink->show() . '</p>';
}

$this->appendArrayVar('headerParams', '<style>.assignment-admin-card{margin-top:1rem;padding:1.25rem;border:1px solid #dbe4e7;border-radius:12px;background:#fff}.assignment-admin-card h2{margin-top:0}.assignment-summary{width:100%;border-collapse:collapse}.assignment-summary th,.assignment-summary td{padding:.65rem;text-align:left;border-bottom:1px solid #e2e8f0}.assignment-mark-link{display:inline-block;padding:.45rem .75rem;border-radius:6px;background:#295351;color:#fff!important}.assignment-policy-option{display:block;margin:.65rem 0}.assignment-admin-card button{margin-top:.5rem;padding:.6rem 1rem}.assignment_main .assignment-row-icon{width:1.15em;height:1.15em;margin-right:.45rem;vertical-align:-.18em}.assignment_main td a,.assignment_link_submittedlist a{display:inline-flex;align-items:center}.assignment_link_submittedlist{margin-top:1.25rem}</style>');
echo "<div class='assignment_main'>$ret</div>";
?>