<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

$this->setLayoutTemplate('gradebook_layout_tpl.php');
$language = $this->objLanguage;
$L = function ($key) use ($language) {
    return $language->languageText('mod_gradebook_'.$key, 'gradebook');
};

$contextCode = $this->contextCode;
$contextObject = $this->objContext;
$courseName = $contextCode ? $contextObject->getMenuText($contextCode) : '';

$plans = $this->getObject('dbgradebookassessmentplans', 'gradebook');
$items = $this->getObject('dbgradebookassessmentplanitems', 'gradebook');
$registry = $this->getObject('assessmentproviderregistry', 'gradebook');
$plan = $plans->findForContext($contextCode);
$planItems = $plan ? $items->getForPlan($plan['id']) : array();
$planRows = array();

foreach ((array) $planItems as $item) {
    $provider = $registry->get($item['provider_key']);
    $adapter = $provider ? $registry->adapter($item['provider_key']) : false;
    $activity = is_object($adapter)
        && is_callable(array($adapter, 'getActivity'))
        ? $adapter->getActivity($contextCode, $item['activity_id'])
        : false;
    $planRows[] = array(
        'item' => $item,
        'provider' => $provider,
        'adapter' => $adapter,
        'activity' => $activity,
        'title' => is_array($activity) && !empty($activity['name'])
            ? $activity['name'] : $item['name']
    );
}

$userIds = (array) $this->objGradebook->getStudentInContextInfo('userid');
$usernames = (array) $this->objGradebook->getStudentInContextInfo('username');
$firstNames = (array) $this->objGradebook->getStudentInContextInfo('firstname');
$surnames = (array) $this->objGradebook->getStudentInContextInfo('surname');
$studentCount = min(count($userIds), count($usernames), count($firstNames), count($surnames));

$selectedItemId = trim((string) $this->getParam('plan_item', ''));
$selectedRow = false;
foreach ($planRows as $row) {
    if ((string) $row['item']['id'] === $selectedItemId) {
        $selectedRow = $row;
        break;
    }
}

$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

echo '<div class="gradebook-home chisimba-workspace">';
echo '<h1>'.$esc($courseName).' '.$esc($L('title')).'</h1>';

echo '<div class="gradebook-home-actions chisimba-actions">'
    .'<a class="button" href="'.$this->uri(array('action'=>'assessmentPlan')).'">'.$esc($L('assessmentplan')).'</a>'
    .' &nbsp; <a class="button" href="'.$this->uri(array('action'=>'assessmentSheet')).'">'.$esc($L('assessmentsheet')).'</a>'
    .'</div>';

echo '<section class="gradebook-home-section">';
echo '<h2>'.$esc($L('course')).'</h2>';
if ($studentCount === 0) {
    echo '<p>'.$esc($L('nostudents')).'</p>';
} else {
    echo '<div class="chisimba-table-wrap"><table class="chisimba-table gradebook-summary-table">'
        .'<thead><tr>'
        .'<th>'.$esc($L('studentNumber')).'</th>'
        .'<th>'.$esc($L('student')).'</th>'
        .'<th>'.$esc($L('currentcoursemark')).'</th>'
        .'</tr></thead><tbody>';
    for ($i = 0; $i < $studentCount; $i++) {
        $rows = $this->studentAssessmentRows($userIds[$i]);
        $courseMark = 0.0;
        $hasMark = false;
        foreach ((array) $rows as $resultRow) {
            if ($resultRow['weighted_mark'] !== null) {
                $courseMark += (float) $resultRow['weighted_mark'];
                $hasMark = true;
            }
        }
        echo '<tr>'
            .'<td>'.$esc($usernames[$i]).'</td>'
            .'<td>'.$esc(trim($firstNames[$i].' '.$surnames[$i])).'</td>'
            .'<td>'.($hasMark ? $esc(number_format($courseMark, 2)).'%' : '&mdash;').'</td>'
            .'</tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';

echo '<section class="gradebook-home-section">';
echo '<h2>'.$esc($L('viewByAssessment')).'</h2>';
if (empty($planRows)) {
    echo '<p>'.$esc($L('noassessmentplanitems')).'</p>';
} else {
    echo '<form method="get" action="'.$this->uri(array()).'">'
        .'<input type="hidden" name="module" value="gradebook">'
        .'<label>'.$esc($L('assessment')).' '
        .'<select name="plan_item" onchange="this.form.submit()">'
        .'<option value="">'.$esc($L('selectassessmentactivity')).'</option>';
    foreach ($planRows as $row) {
        $providerLabel = $row['provider'] ? $row['provider']['label'] : $row['item']['provider_module'];
        $selected = (string) $row['item']['id'] === $selectedItemId ? ' selected' : '';
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
            $normalisedStatus = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $result['status']));
            $statusKey = 'result_'.str_replace('_', '', $normalisedStatus);
            $statusText = $language->languageText('mod_gradebook_'.$statusKey, 'gradebook');
            if ($statusText === 'mod_gradebook_'.$statusKey) {
                $statusText = $result['status'];
            }
            $mark = isset($result['mark_percent']) && is_numeric($result['mark_percent'])
                ? number_format(max(0, min(100, (float) $result['mark_percent'])), 2).'%' : '&mdash;';
            echo '<tr>'
                .'<td>'.$esc($usernames[$i]).'</td>'
                .'<td>'.$esc(trim($firstNames[$i].' '.$surnames[$i])).'</td>'
                .'<td>'.$mark.'</td>'
                .'<td>'.$esc($statusText).'</td>'
                .'</tr>';
        }
        echo '</tbody></table></div>';
    }
}
echo '</section>';
echo '</div>';
?>