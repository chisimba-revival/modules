<?php
/** Focused lecturer drill-down for one Assessment Plan activity. */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
$this->setLayoutTemplate('gradebook_layout_tpl.php');
$esc = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$number = function ($value) { return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.'); };
$L = function ($key, $fallback='') { return $this->objLanguage->languageText('mod_gradebook_'.$key, 'gradebook', $fallback); };
$plans = $this->getObject('dbgradebookassessmentplans', 'gradebook');
$items = $this->getObject('dbgradebookassessmentplanitems', 'gradebook');
$registry = $this->getObject('assessmentproviderregistry', 'gradebook');
$plan = $plans->findForContext($this->contextCode);
$planItems = $plan ? $items->getForPlan($plan['id']) : array();
$rows = array();
foreach ($planItems as $item) {
    $provider = $registry->get($item['provider_key']);
    $adapter = $provider ? $registry->adapter($item['provider_key']) : false;
    $activity = is_object($adapter) ? $adapter->getActivity($this->contextCode, $item['activity_id']) : false;
    $rows[] = array('item'=>$item, 'provider'=>$provider, 'adapter'=>$adapter,
        'title'=>is_array($activity) && !empty($activity['name']) ? $activity['name'] : $item['name']);
}
$selectedId = trim((string) $this->getParam('plan_item', ''));
if ($selectedId === '' && count($rows) === 1) { $selectedId = (string) $rows[0]['item']['id']; }
$selected = false;
foreach ($rows as $row) { if ((string) $row['item']['id'] === $selectedId) { $selected = $row; break; } }
$userIds = (array) $this->objGradebook->getStudentInContextInfo('userid');
$usernames = (array) $this->objGradebook->getStudentInContextInfo('username');
$firstNames = (array) $this->objGradebook->getStudentInContextInfo('firstname');
$surnames = (array) $this->objGradebook->getStudentInContextInfo('surname');
$studentCount = min(count($userIds), count($usernames), count($firstNames), count($surnames));
$statusLabels = array('not_attempted'=>$L('result_notattempted','Not yet attempted'), 'in_progress'=>$L('result_inprogress','In progress'), 'submitted'=>$L('result_submitted','Submitted'), 'marked'=>$L('result_marked','Marked'));
?>
<div class="chisimba-workspace gradebook-assessment-drilldown">
<h1><?php echo $esc($L('viewByAssessment', 'View grades by assessment')); ?></h1>
<nav class="chisimba-actions" aria-label="<?php echo $esc($L('gradebooknavigation', 'Gradebook navigation')); ?>"><a class="button chisimba-button-secondary" href="<?php echo $this->uri(array()); ?>"><?php echo $esc($L('title', 'Gradebook')); ?></a></nav>
<?php if (empty($rows)) { ?><p><?php echo $esc($L('noassessmentplanitems', 'No assessments are in the Assessment Plan.')); ?></p><?php } else { ?>
<form method="get" action="<?php echo $this->uri(array()); ?>" class="chisimba-form gradebook-assessment-selector">
<input type="hidden" name="module" value="gradebook"><input type="hidden" name="action" value="assessmentResults">
<div class="chisimba-form-field"><label for="gradebook-plan-item"><?php echo $esc($L('assessment', 'Assessment')); ?></label><select id="gradebook-plan-item" name="plan_item" onchange="this.form.submit()">
<?php foreach ($rows as $row) { $id=(string)$row['item']['id']; ?><option value="<?php echo $esc($id); ?>"<?php echo $id===$selectedId?' selected':''; ?>><?php echo $esc(($row['item']['short_name'] ?? '').' — '.$row['title']); ?></option><?php } ?>
</select></div></form>
<?php if ($selected) { ?><h2><?php echo $esc($selected['title']); ?></h2><div class="chisimba-table-wrap"><table class="chisimba-table"><thead><tr><th><?php echo $esc($L('studentNumber','Student number')); ?></th><th><?php echo $esc($L('student','Student')); ?></th><th><?php echo $esc($L('mark','Mark (%)')); ?></th><th><?php echo $esc($L('activitystatus','Activity status')); ?></th></tr></thead><tbody>
<?php for ($i=0;$i<$studentCount;$i++) { $result=array('status'=>'not_attempted','mark_percent'=>null); if (is_object($selected['adapter']) && is_callable(array($selected['adapter'],'getStudentResult'))) { $candidate=$selected['adapter']->getStudentResult($this->contextCode,$selected['item']['activity_id'],$userIds[$i],$selected['item']['result_rule'] ?? 'latest_completed'); if(is_array($candidate)&&!empty($candidate['status'])){$result=$candidate;} } ?>
<tr><td><?php echo $esc($usernames[$i]); ?></td><td><?php echo $esc(trim($firstNames[$i].' '.$surnames[$i])); ?></td><td><?php echo is_numeric($result['mark_percent'])?$esc($number($result['mark_percent'])).'%':'&mdash;'; ?></td><td><?php echo $esc($statusLabels[$result['status']] ?? $result['status']); ?></td></tr><?php } ?>
</tbody></table></div><?php } ?>
<?php } ?></div>
