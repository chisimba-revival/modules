<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
$this->setLayoutTemplate('gradebook_layout_tpl.php');
$tests = isset($assessmentPlanMcqTests) && is_array($assessmentPlanMcqTests) ? $assessmentPlanMcqTests : array();
$error = (string) $this->getParam('planerror', '');
?>
<h1><?php echo $this->objLanguage->languageText('mod_gradebook_addmcqassessment', 'gradebook'); ?></h1>
<p><?php echo $this->objLanguage->languageText('mod_gradebook_addmcqassessment_description', 'gradebook'); ?></p>
<?php if ($error !== ''): ?><p class="error"><?php echo $this->objLanguage->languageText('mod_gradebook_planerror_'.$error, 'gradebook'); ?></p><?php endif; ?>
<?php if (empty($tests)): ?>
  <p><?php echo $this->objLanguage->languageText('mod_gradebook_nomcqtests', 'gradebook'); ?></p>
<?php else: ?>
<form method="post" action="<?php echo $this->uri(array('action'=>'assessmentPlanSaveMcq')); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($assessmentPlanCsrf, ENT_QUOTES, 'UTF-8'); ?>">
  <p><label for="activity_id"><?php echo $this->objLanguage->languageText('mod_gradebook_selectmcqtest', 'gradebook'); ?></label><br>
  <select id="activity_id" name="activity_id" required>
    <option value=""><?php echo $this->objLanguage->languageText('mod_gradebook_selectmcqtest', 'gradebook'); ?></option>
    <?php foreach ($tests as $test): ?><option value="<?php echo htmlspecialchars($test['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($test['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
  </select></p>
  <p><?php echo $this->objLanguage->languageText('mod_gradebook_addmcqassessment_note', 'gradebook'); ?></p>
  <p><button type="submit"><?php echo $this->objLanguage->languageText('mod_gradebook_saveassessmentplanitem', 'gradebook'); ?></button></p>
</form>
<?php endif; ?>
<p><a href="<?php echo $this->uri(array('action'=>'assessmentPlan')); ?>"><?php echo $this->objLanguage->languageText('mod_gradebook_goback', 'gradebook'); ?></a></p>
