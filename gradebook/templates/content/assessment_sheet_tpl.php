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
$iconUri = $this->getResourceUri('icons/lucide/', 'ui');
$groupIcons = array('formative' => 'book-open.svg', 'summative' => 'list-checks.svg', 'unclassified' => 'circle-alert.svg');
$heading = new htmlheading(); $heading->type = 1; $heading->str = $this->objLanguage->languageText('mod_gradebook_assessmentsheet', 'gradebook'); echo $heading->show();
?>
<style>
.assessment-sheet-workspace{max-width:70rem;margin:1.25rem auto 2rem}.assessment-sheet-intro{max-width:52rem;margin:0 0 1.4rem;color:var(--chisimba-text,#444)}.assessment-sheet-notice{display:flex;align-items:center;gap:.55rem;margin:0 0 1rem;padding:.85rem 1rem;border:1px solid #86c99a;border-radius:.55rem;background:#eaf8ee;color:#155724;font-weight:700;box-shadow:0 .1rem .35rem rgba(21,87,36,.10)}.assessment-sheet-notice:before{content:'✓';display:inline-flex;align-items:center;justify-content:center;width:1.35rem;height:1.35rem;border-radius:50%;background:#238636;color:#fff;font-weight:700;line-height:1;flex:0 0 auto}.assessment-sheet-notice.error{border-color:#e3a0a0;background:#fff0f0;color:#8a1f1f}.assessment-sheet-notice.error:before{content:'!';background:#c93535}.assessment-sheet-panel{margin:1rem 0 1.35rem;border:1px solid var(--chisimba-border,#ddd);border-radius:.7rem;background:var(--chisimba-surface,#fff);box-shadow:0 .15rem .6rem rgba(17,17,17,.06);overflow:hidden}.assessment-sheet-panel-head{display:flex;align-items:center;gap:.65rem;padding:.85rem 1rem;border-bottom:1px solid var(--chisimba-border,#ddd);background:var(--chisimba-surface-subtle,#f8f9fa)}.assessment-sheet-panel-head img{width:1.35rem;height:1.35rem}.assessment-sheet-panel-head h2{margin:0;font-size:1.1rem}.assessment-sheet-subtotal{margin-left:auto;font-weight:700;white-space:nowrap}.assessment-sheet-provider{padding:1rem 1rem 0}.assessment-sheet-provider:last-child{padding-bottom:1rem}.assessment-sheet-provider h3{margin:0 0 .45rem;font-size:.95rem;color:var(--chisimba-text-muted,#666)}.assessment-sheet-table{width:100%;border-collapse:collapse}.assessment-sheet-table th{padding:.5rem .7rem;text-align:left;font-size:.8rem;color:var(--chisimba-text-muted,#666);font-weight:700}.assessment-sheet-table th:last-child,.assessment-sheet-table td:last-child{text-align:right}.assessment-sheet-table td{padding:.72rem;border-top:1px solid var(--chisimba-border,#ddd);vertical-align:middle}.assessment-sheet-title{font-weight:650}.assessment-weight-wrap{display:inline-flex;align-items:center;gap:.35rem}.assessment-weight{width:5.75rem;padding:.42rem .5rem;text-align:right;font-variant-numeric:tabular-nums}.assessment-sheet-summary{display:flex;flex-wrap:wrap;gap:.75rem 1.5rem;align-items:center;margin:1.25rem 0;padding:1rem;border-radius:.65rem;background:var(--chisimba-primary-soft,#e7f4fd);border:1px solid #b9def7;font-variant-numeric:tabular-nums}.assessment-sheet-summary strong{color:var(--chisimba-primary-dark,#005b9f)}.assessment-sheet-actions{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;margin-top:1.25rem}.assessment-sheet-actions button{display:inline-flex;align-items:center;gap:.45rem}.assessment-sheet-actions img{width:1rem;height:1rem;filter:brightness(0) invert(1)}.assessment-sheet-back{margin-top:1.25rem}.assessment-sheet-empty{padding:1rem;border:1px dashed var(--chisimba-border-strong,#ccc);border-radius:.65rem;background:var(--chisimba-surface-subtle,#f8f9fa)}@media(max-width:42rem){.assessment-sheet-provider{padding:.85rem .7rem 0}.assessment-sheet-table th:nth-child(2),.assessment-sheet-table td:nth-child(2){display:none}.assessment-sheet-table td,.assessment-sheet-table th{padding:.65rem .45rem}.assessment-sheet-summary{display:block}.assessment-sheet-summary span{display:block;margin:.35rem 0}}
</style>
<div class="assessment-sheet-workspace">
<nav class="chisimba-actions" aria-label="<?php echo $this->objLanguage->languageText('mod_gradebook_gradebooknavigation', 'gradebook', 'Gradebook navigation'); ?>">
<a class="button chisimba-button-secondary" href="<?php echo $this->uri(array()); ?>"><?php echo $this->objLanguage->languageText('mod_gradebook_title', 'gradebook', 'Gradebook'); ?></a>
<a class="button" href="<?php echo $this->uri(array('action'=>'assessmentPlan')); ?>"><?php echo $this->objLanguage->languageText('mod_gradebook_assessmentplan', 'gradebook', 'Assessment plan'); ?></a>
</nav>
<p class="assessment-sheet-intro"><?php echo $this->objLanguage->languageText('mod_gradebook_assessmentsheet_description', 'gradebook'); ?></p>
<?php if ($message !== '') { ?><p class="confirm assessment-sheet-notice" role="status" aria-live="polite"><?php echo $this->objLanguage->languageText('mod_gradebook_planmessage_'.$message, 'gradebook'); ?></p><?php } ?>
<?php if ($error !== '') { ?><p class="error assessment-sheet-notice"><?php echo $this->objLanguage->languageText('mod_gradebook_planerror_'.$error, 'gradebook'); ?></p><?php } ?>
<?php if (empty($rows)) { ?><p class="assessment-sheet-empty"><?php echo $this->objLanguage->languageText('mod_gradebook_noassessmentplanitems', 'gradebook'); ?></p><?php } else { ?>
<form method="post" action="<?php echo $this->uri(array('action'=>'assessmentSheetSave')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($assessmentPlanCsrf, ENT_QUOTES, 'UTF-8'); ?>">
<?php foreach ($groups as $classification => $groupRows): if (empty($groupRows)) continue; $providers = array(); foreach ($groupRows as $row) { $providers[$row['provider']][] = $row; } ?>
<section class="assessment-sheet-panel assessment-sheet-panel-<?php echo $classification; ?>">
<header class="assessment-sheet-panel-head"><img src="<?php echo $iconUri.$groupIcons[$classification]; ?>" alt="" aria-hidden="true"><h2><?php echo $this->objLanguage->languageText('mod_gradebook_classification_'.$classification, 'gradebook'); ?></h2><span class="assessment-sheet-subtotal"><span class="assessment-sheet-subtotal-value" data-classification="<?php echo $classification; ?>">0</span>%</span></header>
<?php foreach ($providers as $provider => $providerRows): ?>
<div class="assessment-sheet-provider"><h3><?php echo htmlspecialchars($provider, ENT_QUOTES, 'UTF-8'); ?></h3><table class="assessment-sheet-table"><thead><tr><th><?php echo $this->objLanguage->languageText('mod_gradebook_assessment', 'gradebook'); ?></th><th><?php echo $this->objLanguage->languageText('mod_gradebook_shortname', 'gradebook', 'Short name'); ?></th><th><?php echo $this->objLanguage->languageText('mod_gradebook_contribution', 'gradebook'); ?></th></tr></thead><tbody>
<?php foreach ($providerRows as $row): $item = $row['item']; ?><tr><td class="assessment-sheet-title"><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td><td><input type="text" maxlength="16" name="short_name[<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($item['short_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></td><td><label class="assessment-weight-wrap"><input class="assessment-weight" data-classification="<?php echo $classification; ?>" type="number" min="0" max="100" step="0.1" name="weight[<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars(number_format((float) $item['weight'], 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"><span>%</span></label></td></tr><?php endforeach; ?>
</tbody></table></div><?php endforeach; ?>
</section><?php endforeach; ?>
<aside class="assessment-sheet-summary" aria-live="polite"><span><strong><?php echo $this->objLanguage->languageText('mod_gradebook_totalallocated', 'gradebook'); ?></strong>: <span id="assessment-sheet-total">0</span>%</span><span><strong><?php echo $this->objLanguage->languageText('mod_gradebook_remaining', 'gradebook'); ?></strong>: <span id="assessment-sheet-remaining">100</span>%</span></aside>
<p class="assessment-sheet-actions"><button type="submit"><img src="<?php echo $iconUri; ?>list-checks.svg" alt="" aria-hidden="true"><span><?php echo $this->objLanguage->languageText('mod_gradebook_saveassessmentsheet', 'gradebook'); ?></span></button></p>
</form><?php } ?>

<?php
$studentIds = (array) $this->objGradebook->getStudentInContextInfo('userid');
$studentNumbers = (array) $this->objGradebook->getStudentInContextInfo('username');
$studentFirstNames = (array) $this->objGradebook->getStudentInContextInfo('firstname');
$studentSurnames = (array) $this->objGradebook->getStudentInContextInfo('surname');
$studentTotal = min(count($studentIds), count($studentNumbers), count($studentFirstNames), count($studentSurnames));
$statusLabels = array(
    'not_attempted'=>$this->objLanguage->languageText('mod_gradebook_result_notattempted', 'gradebook'),
    'in_progress'=>$this->objLanguage->languageText('mod_gradebook_result_inprogress', 'gradebook'),
    'submitted'=>$this->objLanguage->languageText('mod_gradebook_result_submitted', 'gradebook'),
    'marked'=>$this->objLanguage->languageText('mod_gradebook_result_marked', 'gradebook'),
);
$formatPercent = function ($value) {
    return rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.').'%';
};
?>
<section class="assessment-sheet-panel gradebook-mark-matrix" aria-labelledby="gradebook-mark-matrix-title">
<header class="assessment-sheet-panel-head"><img src="<?php echo $iconUri; ?>table-2.svg" alt="" aria-hidden="true"><h2 id="gradebook-mark-matrix-title"><?php echo $this->objLanguage->languageText('mod_gradebook_classmarkmatrix', 'gradebook', 'Class marks'); ?></h2></header>
<?php if ($studentTotal === 0 || empty($rows)) { ?>
<p class="assessment-sheet-empty"><?php echo $this->objLanguage->languageText('mod_gradebook_classmarkmatrix_empty', 'gradebook', 'Add learners and Assessment Sheet activities to see the class marks table.'); ?></p>
<?php } else { ?>
<div class="chisimba-table-wrap"><table class="chisimba-table"><thead><tr>
<th><?php echo $this->objLanguage->languageText('mod_gradebook_student', 'gradebook', 'Student'); ?></th>
<?php foreach ($rows as $row) { ?><th><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></th><?php } ?>
</tr></thead><tbody>
<?php for ($studentIndex=0; $studentIndex<$studentTotal; $studentIndex++) { ?>
<tr><th scope="row"><?php echo htmlspecialchars(trim($studentFirstNames[$studentIndex].' '.$studentSurnames[$studentIndex]), ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($studentNumbers[$studentIndex], ENT_QUOTES, 'UTF-8'); ?></small></th>
<?php foreach ($rows as $row) {
    $result = array('status'=>'not_attempted', 'mark_percent'=>null);
    if (is_object($row['adapter']) && is_callable(array($row['adapter'], 'getStudentResult'))) {
        $candidate = $row['adapter']->getStudentResult(
            $this->contextCode,
            $row['item']['activity_id'],
            $studentIds[$studentIndex],
            !empty($row['item']['result_rule']) ? $row['item']['result_rule'] : 'latest_completed'
        );
        if (is_array($candidate) && !empty($candidate['status'])) { $result = $candidate; }
    }
    $status = isset($statusLabels[$result['status']]) ? $statusLabels[$result['status']] : $result['status'];
?><td><?php if (is_numeric($result['mark_percent'])) { echo '<strong>'.$formatPercent($result['mark_percent']).'</strong>'; } else { echo '&mdash;<br><small>'.htmlspecialchars($status, ENT_QUOTES, 'UTF-8').'</small>'; } ?></td><?php } ?>
</tr><?php } ?>
</tbody></table></div>
<?php } ?>
</section>
</div>
<script>(function(){function update(){var total=0,subtotals={},els=document.getElementsByClassName('assessment-weight');for(var i=0;i<els.length;i++){var value=parseFloat(els[i].value)||0,classification=els[i].getAttribute('data-classification');total+=value;subtotals[classification]=(subtotals[classification]||0)+value;}var format=function(value){return value.toFixed(1).replace(/\.?(?:0+)$/,'');};document.getElementById('assessment-sheet-total').textContent=format(total);document.getElementById('assessment-sheet-remaining').textContent=format(100-total);var labels=document.getElementsByClassName('assessment-sheet-subtotal-value');for(var j=0;j<labels.length;j++){labels[j].textContent=format(subtotals[labels[j].getAttribute('data-classification')]||0);}}var fields=document.getElementsByClassName('assessment-weight');for(var i=0;i<fields.length;i++){fields[i].addEventListener('input',update);}if(document.getElementById('assessment-sheet-total')){update();}}());</script>
