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

$selectedLearnerId = trim((string)$this->getParam('learner_id', ''));
$selectedLearnerIndex = null;
for ($i = 0; $i < $studentCount; $i++) {
    if ((string)$userIds[$i] === $selectedLearnerId) {
        $selectedLearnerIndex = $i;
        break;
    }
}

$statusLabels = array(
    'not_attempted' => $L('result_notattempted'),
    'in_progress' => $L('result_inprogress'),
    'submitted' => $L('result_submitted'),
    'marked' => $L('result_marked')
);

$now = time();
$isOpenNow = function ($item) use ($now) {
    if (isset($item['opening_enabled'])
        && strtoupper((string)$item['opening_enabled']) === 'Y'
        && !empty($item['opening_date'])) {
        $opening = strtotime((string)$item['opening_date']);
        if ($opening !== false && $now < $opening) {
            return false;
        }
    }
    if (isset($item['closing_enabled'])
        && strtoupper((string)$item['closing_enabled']) === 'Y'
        && !empty($item['closing_date'])) {
        $closing = strtotime((string)$item['closing_date']);
        if ($closing !== false && $now > $closing) {
            return false;
        }
    }
    return true;
};

echo '<div class="gradebook-home chisimba-workspace">';
echo '<h1>'.$esc($contextName).' '.$esc($L('title')).'</h1>';

echo '<div class="gradebook-home-actions chisimba-actions">'
    .'<a class="button" href="'.$this->uri(array('action'=>'assessmentPlan')).'">'.$esc($L('assessmentplan')).'</a>'
    .'<a class="button" href="'.$this->uri(array('action'=>'assessmentSheet')).'">'.$esc($L('assessmentsheet')).'</a>'
    .'</div>';

echo '<section class="gradebook-home-section">';
echo '<h2>'.$esc($L('learnersummary')).'</h2>';

if ($studentCount === 0) {
    echo '<p>'.$esc($L('nostudents')).'</p>';
} else {
    echo '<div class="chisimba-table-wrap"><table class="chisimba-table gradebook-summary-table">'
        .'<thead><tr>'
        .'<th>'.$esc($L('studentNumber')).'</th>'
        .'<th>'.$esc($L('student')).'</th>'
        .'<th>'.$esc($L('assessmentscompleted')).'</th>'
        .'<th>'.$esc($L('totalassessments')).'</th>'
        .'<th>'.$esc($L('opennow')).'</th>'
        .'</tr></thead><tbody>';

    for ($i = 0; $i < $studentCount; $i++) {
        $resultRows = $this->studentAssessmentRows($userIds[$i]);
        $completed = 0;
        foreach ($resultRows as $resultRow) {
            if ($resultRow['mark_percent'] !== null) {
                $completed++;
            }
        }

        $openNow = 0;
        foreach ((array)$planItems as $item) {
            if ($isOpenNow($item)) {
                $openNow++;
            }
        }

        $detailUri = $this->uri(array('learner_id'=>$userIds[$i]));
        $name = trim($firstNames[$i].' '.$surnames[$i]);

        echo '<tr>'
            .'<td>'.$esc($usernames[$i]).'</td>'
            .'<td><a href="'.$detailUri.'">'.$esc($name).'</a></td>'
            .'<td>'.$esc($completed).'</td>'
            .'<td>'.$esc(count($planItems)).'</td>'
            .'<td>'.$esc($openNow).'</td>'
            .'</tr>';
    }

    echo '</tbody></table></div>';
}

echo '</section>';

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

$planRows = array();
foreach ((array)$planItems as $item) {
    $provider = $registry->get($item['provider_key']);
    $adapter = $provider ? $registry->adapter($item['provider_key']) : false;
    $activity = is_object($adapter) && is_callable(array($adapter, 'getActivity'))
        ? $adapter->getActivity($contextCode, $item['activity_id'])
        : false;
    $planRows[] = array(
        'item'=>$item,
        'provider'=>$provider,
        'adapter'=>$adapter,
        'title'=>is_array($activity) && !empty($activity['name'])
            ? $activity['name'] : $item['name']
    );
}

$selectedItemId = trim((string)$this->getParam('plan_item', ''));
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
