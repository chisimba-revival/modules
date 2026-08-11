<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
$this->setLayoutTemplate('gradebook_layout_tpl.php');
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('htmltable', 'htmlelements');
$rows = isset($assessmentPlanRows) && is_array($assessmentPlanRows) ? $assessmentPlanRows : array();
$message = (string) $this->getParam('planmessage', '');
$error = (string) $this->getParam('planerror', '');
$groups = array('formative' => array(), 'summative' => array(), 'unclassified' => array());
foreach ($rows as $row) { $groups[isset($groups[$row['classification']]) ? $row['classification'] : 'unclassified'][] = $row; }
$heading = new htmlheading(); $heading->type = 1; $heading->str = $this->objLanguage->languageText('mod_gradebook_assessmentsheet', 'gradebook'); echo $heading->show();
echo '<p>'.$this->objLanguage->languageText('mod_gradebook_assessmentsheet_description', 'gradebook').'</p>';
if ($message !== '') { echo '<p class="confirm">'.$this->objLanguage->languageText('mod_gradebook_planmessage_'.$message, 'gradebook').'</p>'; }
if ($error !== '') { echo '<p class="error">'.$this->objLanguage->languageText('mod_gradebook_planerror_'.$error, 'gradebook').'</p>'; }
if (empty($rows)) { echo '<p>'.$this->objLanguage->languageText('mod_gradebook_noassessmentplanitems', 'gradebook').'</p>'; }
else {
?><form method="post" action="<?php echo $this->uri(array('action'=>'assessmentSheetSave')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($assessmentPlanCsrf, ENT_QUOTES, 'UTF-8'); ?>">
<?php foreach ($groups as $classification => $groupRows): if (empty($groupRows)) continue; ?>
<h2><?php echo $this->objLanguage->languageText('mod_gradebook_classification_'.$classification, 'gradebook'); ?></h2>
<table class="assessment-sheet" style="width:100%"><tr><th><?php echo $this->objLanguage->languageText('mod_gradebook_assessment', 'gradebook'); ?></th><th><?php echo $this->objLanguage->languageText('mod_gradebook_provider', 'gradebook'); ?></th><th><?php echo $this->objLanguage->languageText('mod_gradebook_contribution', 'gradebook'); ?></th></tr>
<?php foreach ($groupRows as $row): $item=$row['item']; ?><tr><td><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($row['provider'], ENT_QUOTES, 'UTF-8'); ?></td><td><input class="assessment-weight" type="number" min="0" max="100" step="0.001" name="weight[<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($item['weight'], ENT_QUOTES, 'UTF-8'); ?>">%</td></tr><?php endforeach; ?>
</table><?php endforeach; ?>
<p><strong><?php echo $this->objLanguage->languageText('mod_gradebook_totalallocated', 'gradebook'); ?></strong>: <span id="assessment-sheet-total">0</span>% &mdash; <strong><?php echo $this->objLanguage->languageText('mod_gradebook_remaining', 'gradebook'); ?></strong>: <span id="assessment-sheet-remaining">100</span>%</p>
<p><button type="submit"><?php echo $this->objLanguage->languageText('mod_gradebook_saveassessmentsheet', 'gradebook'); ?></button></p>
</form><script>(function(){function total(){var n=0,els=document.getElementsByClassName('assessment-weight');for(var i=0;i<els.length;i++){n+=parseFloat(els[i].value)||0;}document.getElementById('assessment-sheet-total').textContent=n.toFixed(3);document.getElementById('assessment-sheet-remaining').textContent=(100-n).toFixed(3);}var els=document.getElementsByClassName('assessment-weight');for(var i=0;i<els.length;i++){els[i].addEventListener('input',total);}total();}());</script><?php }
?><p><a href="<?php echo $this->uri(array('action'=>'assessmentPlan')); ?>"><?php echo $this->objLanguage->languageText('mod_gradebook_backtoassessmentplan', 'gradebook'); ?></a></p>
