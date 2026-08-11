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
  <p><label for="title"><?php echo $this->objLanguage->languageText('mod_gradebook_planitemtitle', 'gradebook'); ?></label><br><input id="title" name="title" type="text" maxlength="255"></p>
  <p><label for="weight"><?php echo $this->objLanguage->languageText('mod_gradebook_planitemweight', 'gradebook'); ?></label><br><input id="weight" name="weight" type="number" min="0" max="100" step="0.001" value="0" required></p>
  <p><label for="opening_date"><?php echo $this->objLanguage->languageText('mod_gradebook_planopeningdate', 'gradebook'); ?></label><br><input id="opening_date" name="opening_date" type="date"></p>
  <p><label for="closing_date"><?php echo $this->objLanguage->languageText('mod_gradebook_planclosingdate', 'gradebook'); ?></label><br><input id="closing_date" name="closing_date" type="date"></p>
  <p><label><input type="checkbox" name="include_in_course_mark" value="Y" checked> <?php echo $this->objLanguage->languageText('mod_gradebook_includeincoursemark', 'gradebook'); ?></label><br>
  <label><input type="checkbox" name="required_for_completion" value="Y"> <?php echo $this->objLanguage->languageText('mod_gradebook_requiredforcompletion', 'gradebook'); ?></label></p>
  <p><strong><?php echo $this->objLanguage->languageText('mod_gradebook_resultrule', 'gradebook'); ?></strong>: <?php echo $this->objLanguage->languageText('mod_gradebook_latestcompleted', 'gradebook'); ?></p>
  <p><button type="submit"><?php echo $this->objLanguage->languageText('mod_gradebook_saveassessmentplanitem', 'gradebook'); ?></button></p>
</form>
<?php endif; ?>
<p><a href="<?php echo $this->uri(array('action'=>'assessmentPlan')); ?>"><?php echo $this->objLanguage->languageText('mod_gradebook_goback', 'gradebook'); ?></a></p>
