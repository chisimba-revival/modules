<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
$this->setLayoutTemplate('gradebook_layout_tpl.php');
$provider = isset($assessmentPlanProvider) && is_array($assessmentPlanProvider) ? $assessmentPlanProvider : array();
$activities = isset($assessmentPlanActivities) && is_array($assessmentPlanActivities) ? $assessmentPlanActivities : array();
$error = (string) $this->getParam('planerror', '');
?>
<h1><?php echo $this->objLanguage->languageText('mod_gradebook_addproviderassessment', 'gradebook'); ?></h1>
<p><strong><?php echo htmlspecialchars(isset($provider['label']) ? $provider['label'] : '', ENT_QUOTES, 'UTF-8'); ?></strong></p>
<p><?php echo $this->objLanguage->languageText('mod_gradebook_addproviderassessment_description', 'gradebook'); ?></p>
<?php if ($error !== ''): ?><p class="error"><?php echo $this->objLanguage->languageText('mod_gradebook_planerror_'.$error, 'gradebook'); ?></p><?php endif; ?>
<?php if (empty($activities)): ?>
  <p><?php echo $this->objLanguage->languageText('mod_gradebook_noassessmentactivities', 'gradebook'); ?></p>
<?php else: ?>
<form method="post" action="<?php echo $this->uri(array('action'=>'assessmentPlanSaveProvider')); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($assessmentPlanCsrf, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" name="provider_key" value="<?php echo htmlspecialchars($provider['key'], ENT_QUOTES, 'UTF-8'); ?>">
  <p><label for="activity_id"><?php echo $this->objLanguage->languageText('mod_gradebook_selectassessmentactivity', 'gradebook'); ?></label><br>
  <select id="activity_id" name="activity_id" required>
    <option value=""><?php echo $this->objLanguage->languageText('mod_gradebook_selectassessmentactivity', 'gradebook'); ?></option>
    <?php foreach ($activities as $activity): ?><option value="<?php echo htmlspecialchars($activity['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($activity['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
  </select></p>
  <p><button type="submit"><?php echo $this->objLanguage->languageText('mod_gradebook_saveassessmentplanitem', 'gradebook'); ?></button></p>
</form>
<?php endif; ?>
<p><a href="<?php echo $this->uri(array('action'=>'assessmentPlan')); ?>"><?php echo $this->objLanguage->languageText('mod_gradebook_goback', 'gradebook'); ?></a></p>
