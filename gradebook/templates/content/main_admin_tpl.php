<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

$this->setLayoutTemplate('gradebook_layout_tpl.php');

$language = $this->objLanguage;
$L = function ($key) use ($language) {
    return $language->languageText('mod_gradebook_'.$key, 'gradebook');
};
$esc = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$number = function ($value) {
    return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
};

$contextCode = $this->contextCode;
$contextObject = $this->objContext;
$contextName = $contextCode ? $contextObject->getMenuText($contextCode) : '';

$plans = $this->getObject('dbgradebookassessmentplans', 'gradebook');
$items = $this->getObject('dbgradebookassessmentplanitems', 'gradebook');
$registry = $this->getObject('assessmentproviderregistry', 'gradebook');
$plan = $plans->findForContext($contextCode);
$planItems = $plan ? $items->getForPlan($plan['id']) : array();

$userIds = (array)$this->objGradebook->getStudentInContextInfo('userid');
$usernames = (array)$this->objGradebook->getStudentInContextInfo('username');
$firstNames = (array)$this->objGradebook->getStudentInContextInfo('firstname');
$surnames = (array)$this->objGradebook->getStudentInContextInfo('surname');
$studentCount = min(count($userIds), count($usernames), count($firstNames), count($surnames));

$statusLabels = array(
    'not_attempted' => $L('result_notattempted'),
    'in_progress' => $L('result_inprogress'),
    'submitted' => $L('result_submitted'),
    'marked' => $L('result_marked')
);
$planRows = array();
foreach ((array)$planItems as $item) {
    $provider = $registry->get($item['provider_key']);
    $adapter = $provider ? $registry->adapter($item['provider_key']) : false;
    $activity = is_object($adapter) && is_callable(array($adapter, 'getActivity'))
        ? $adapter->getActivity($contextCode, $item['activity_id']) : false;
    $planRows[] = array(
        'item'=>$item, 'provider'=>$provider, 'adapter'=>$adapter,
        'title'=>is_array($activity) && !empty($activity['name']) ? $activity['name'] : $item['name'],
    );
}
$matrixDisplay = (string) $this->getParam('display', 'percentage') === 'year_mark' ? 'year_mark' : 'percentage';

echo '<div class="gradebook-home chisimba-workspace">';
echo '<h1>'.$esc($contextName).' '.$esc($L('title')).'</h1>';

echo '<div class="gradebook-home-actions chisimba-actions">'
    .'<a class="button" href="'.$this->uri(array('action'=>'assessmentResults')).'">'.$esc($L('viewByAssessment')).'</a>'
    .'<a class="button" href="'.$this->uri(array('action'=>'assessmentPlan')).'">'.$esc($L('assessmentplan')).'</a>'
    .'<a class="button" href="'.$this->uri(array('action'=>'assessmentSheet')).'">'.$esc($L('assessmentsheet')).'</a>'
    .'</div>';

echo '<section class="gradebook-home-section">';
echo '<h2>'.$esc($language->languageText('mod_gradebook_classmarkmatrix', 'gradebook', 'Class marks')).'</h2>';
echo '<div class="chisimba-actions gradebook-matrix-display">'
    .'<a class="button'.($matrixDisplay === 'percentage' ? '' : ' chisimba-button-secondary').'" href="'.$this->uri(array('display'=>'percentage')).'">'.$esc($language->languageText('mod_gradebook_showpercentage', 'gradebook', 'Show as percentage')).'</a>'
    .'<a class="button'.($matrixDisplay === 'year_mark' ? '' : ' chisimba-button-secondary').'" href="'.$this->uri(array('display'=>'year_mark')).'">'.$esc($language->languageText('mod_gradebook_showyearmark', 'gradebook', 'Show as year mark')).'</a>'
    .'</div>';

if ($studentCount === 0 || empty($planRows)) {
    echo '<p>'.$esc($L('nostudents')).'</p>';
} else {
    echo '<div class="chisimba-table-wrap"><table class="chisimba-table gradebook-mark-matrix"><thead><tr>'
        .'<th>'.$esc($L('student')).'</th>';
    foreach ($planRows as $planRow) {
        $shortName = trim((string) ($planRow['item']['short_name'] ?? ''));
        echo '<th><abbr title="'.$esc($planRow['title']).'">'.$esc($shortName !== '' ? $shortName : $planRow['title']).'</abbr></th>';
    }
    if ($matrixDisplay === 'year_mark') { echo '<th>'.$esc($language->languageText('mod_gradebook_yearmarktotal', 'gradebook', 'Year mark')).'</th>'; }
    echo '</tr></thead><tbody>';

    for ($i = 0; $i < $studentCount; $i++) {
        $name = trim($firstNames[$i].' '.$surnames[$i]);
        $yearMark = 0.0;
        echo '<tr><th scope="row">'.$esc($name).'<br><small>'.$esc($usernames[$i]).'</small></th>';
        foreach ($planRows as $planRow) {
            $result = array('status'=>'not_attempted', 'mark_percent'=>null);
            if (is_object($planRow['adapter']) && is_callable(array($planRow['adapter'], 'getStudentResult'))) {
                $candidate = $planRow['adapter']->getStudentResult($contextCode, $planRow['item']['activity_id'], $userIds[$i], $planRow['item']['result_rule'] ?? 'latest_completed');
                if (is_array($candidate) && !empty($candidate['status'])) { $result = $candidate; }
            }
            $value = is_numeric($result['mark_percent']) ? (float) $result['mark_percent'] : null;
            if ($value !== null && $matrixDisplay === 'year_mark') {
                $value = $value * max(0.0, (float) $planRow['item']['weight']) / 100;
                $yearMark += $value;
            }
            $status = $statusLabels[$result['status']] ?? $result['status'];
            $valueSuffix = $matrixDisplay === 'percentage' ? '%' : '';
            echo '<td title="'.$esc($status).'">'.($value === null ? '&mdash;' : $esc($number($value)).$valueSuffix).'</td>';
        }
        if ($matrixDisplay === 'year_mark') { echo '<td><strong>'.$esc($number($yearMark)).'%</strong></td>'; }
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

echo '</section>';

// The common lecturer journey ends with the class matrix. Detailed assessment
// and learner views have their own focused pages and must not sit below it.
echo '</div>';
return;

if ($selectedLearnerIndex !== null) {
    $learnerId = $userIds[$selectedLearnerIndex];
    $learnerName = trim($firstNames[$selectedLearnerIndex].' '.$surnames[$selectedLearnerIndex]);
    $detailRows = $this->studentAssessmentRows($learnerId);

    echo '<section class="gradebook-home-section">';
    echo '<h2>'.$esc($L('learnerdetail')).': '.$esc($learnerName).'</h2>';
    echo '<p><strong>'.$esc($L('studentNumber')).':</strong> '.$esc($usernames[$selectedLearnerIndex]).'</p>';

    if (empty($detailRows)) {
        echo '<p>'.$esc($L('noassessmentplanstudent')).'</p>';
    } else {
        echo '<div class="chisimba-table-wrap"><table class="chisimba-table gradebook-learner-detail">'
            .'<thead><tr>'
            .'<th>'.$esc($L('assessment')).'</th>'
            .'<th>'.$esc($L('provider')).'</th>'
            .'<th>'.$esc($L('activitystatus')).'</th>'
            .'<th>'.$esc($L('mark')).'</th>'
            .'<th>'.$esc($L('weighting')).'</th>'
            .'<th>'.$esc($L('contributionyearmark')).'</th>'
            .'</tr></thead><tbody>';

        foreach ($detailRows as $row) {
            $status = isset($statusLabels[$row['status']])
                ? $statusLabels[$row['status']]
                : $statusLabels['not_attempted'];

            echo '<tr>'
                .'<td>'.$esc($row['title']).'</td>'
                .'<td>'.$esc($row['provider']).'</td>'
                .'<td>'.$esc($status).'</td>'
                .'<td>'.($row['mark_percent'] === null ? '&mdash;' : $esc($number($row['mark_percent'])).'%').'</td>'
                .'<td>'.$esc($number($row['weight'])).'%</td>'
                .'<td>'.($row['weighted_mark'] === null ? '&mdash;' : $esc($number($row['weighted_mark'])).'%').'</td>'
                .'</tr>';
        }

        echo '</tbody></table></div>';
    }

    echo '<p><a href="'.$this->uri(array()).'">'.$esc($L('backtolearners')).'</a></p>';
    echo '</section>';
}

echo '<section class="gradebook-home-section">';
echo '<h2>'.$esc($L('viewByAssessment')).'</h2>';

$selectedItemId = trim((string)$this->getParam('plan_item', ''));
$selectedItemId = $selectedItemId === '' && count($planRows) === 1
    ? (string) $planRows[0]['item']['id'] : $selectedItemId;
$selectedRow = false;
foreach ($planRows as $row) {
    if ((string)$row['item']['id'] === $selectedItemId) {
        $selectedRow = $row;
        break;
    }
}

if (empty($planRows)) {
    echo '<p>'.$esc($L('noassessmentplanitems')).'</p>';
} else {
    echo '<form method="get" action="'.$this->uri(array()).'">'
        .'<input type="hidden" name="module" value="gradebook">'
        .'<label>'.$esc($L('assessment')).' '
        .'<select name="plan_item" onchange="this.form.submit()">'
        .'<option value="">'.$esc($L('selectassessmentactivity')).'</option>';

    foreach ($planRows as $row) {
        $providerLabel = $row['provider']
            ? $row['provider']['label'] : $row['item']['provider_module'];
        $selected = (string)$row['item']['id'] === $selectedItemId ? ' selected' : '';
        echo '<option value="'.$esc($row['item']['id']).'"'.$selected.'>'
            .$esc($row['title'].' — '.$providerLabel).'</option>';
    }

    echo '</select></label></form>';

    if ($selectedRow) {
        $providerLabel = $selectedRow['provider']
            ? $selectedRow['provider']['label'] : $selectedRow['item']['provider_module'];

        echo '<h3>'.$esc($selectedRow['title']).'</h3>';
        echo '<p>'.$esc($providerLabel).'</p>';
        echo '<div class="chisimba-table-wrap"><table class="chisimba-table gradebook-assessment-results">'
            .'<thead><tr>'
            .'<th>'.$esc($L('studentNumber')).'</th>'
            .'<th>'.$esc($L('student')).'</th>'
            .'<th>'.$esc($L('mark')).'</th>'
            .'<th>'.$esc($L('activitystatus')).'</th>'
            .'</tr></thead><tbody>';

        for ($i = 0; $i < $studentCount; $i++) {
            $result = array('status'=>'not_attempted', 'mark_percent'=>null);
            $adapter = $selectedRow['adapter'];

            if (is_object($adapter) && is_callable(array($adapter, 'getStudentResult'))) {
                $candidate = $adapter->getStudentResult(
                    $contextCode,
                    $selectedRow['item']['activity_id'],
                    $userIds[$i],
                    !empty($selectedRow['item']['result_rule'])
                        ? $selectedRow['item']['result_rule'] : 'latest_completed'
                );
                if (is_array($candidate) && !empty($candidate['status'])) {
                    $result = $candidate;
                }
            }

            $status = isset($statusLabels[$result['status']])
                ? $statusLabels[$result['status']]
                : (string)$result['status'];

            echo '<tr>'
                .'<td>'.$esc($usernames[$i]).'</td>'
                .'<td>'.$esc(trim($firstNames[$i].' '.$surnames[$i])).'</td>'
                .'<td>'.(isset($result['mark_percent']) && is_numeric($result['mark_percent'])
                    ? $esc($number($result['mark_percent'])).'%' : '&mdash;').'</td>'
                .'<td>'.$esc($status).'</td>'
                .'</tr>';
        }

        echo '</tbody></table></div>';
    }
}

echo '</section>';
echo '</div>';
?>
