<?php
/** Native, progressively enhanced activity report. @author Derek Keats */
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $e($this->objLanguage->code2Txt('mod_contextcontent_report_'.$key, 'contextcontent'));
$action = $reportSummary ? 'viewcontextcontentusagedetails' : 'viewlogdetails';
$params = array_merge($reportFilters, ['module'=>'contextcontent','action'=>$action,'studentsonly'=>$reportFilters['studentsonly'] ? '1' : '0']);
$url = fn($extra) => $e($this->uri(array_merge($params, $extra)));
echo '<section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$t($reportSummary ? 'summary' : 'activity').'</h1>';
echo $this->getObject('contextualhelp', 'help')->show('contextcontent', 'activityreports', true);
if (!empty($reportError)) echo '<p role="alert" class="chisimba-notice">'.$t('invaliddates').'</p>';
echo '<form method="get" action="'.$e($this->uri([])).'" class="chisimba-form-grid">';
echo '<input type="hidden" name="module" value="contextcontent"><input type="hidden" name="action" value="'.$action.'">';
foreach (['startdate','enddate'] as $name) echo '<div class="chisimba-form-field"><label for="report-'.$name.'">'.$t($name).'</label><input id="report-'.$name.'" type="date" required name="'.$name.'" value="'.$e($reportFilters[$name]).'"></div>';
echo '<div class="chisimba-form-field"><label for="report-search">'.$t('search').'</label><input id="report-search" type="text" name="q" maxlength="100" value="'.$e($reportFilters['q']).'"></div>';
echo '<div class="chisimba-form-field"><label for="report-sort">'.$t('sort').'</label><select id="report-sort" name="sort">';
foreach (($reportSummary ? ['accesses','name'] : ['recent','oldest','name']) as $sort) echo '<option value="'.$sort.'"'.($reportFilters['sort'] === $sort ? ' selected' : '').'>'.$t($sort).'</option>';
echo '</select></div>';
echo '<div class="chisimba-form-field"><label for="report-people">'.$t('people').'</label><select id="report-people" name="studentsonly"><option value="1"'.($reportFilters['studentsonly'] ? ' selected' : '').'>'.$t('students').'</option><option value="0"'.(!$reportFilters['studentsonly'] ? ' selected' : '').'>'.$t('everyone').'</option></select></div><div class="chisimba-form-actions"><button type="submit">'.$t('view').'</button></div></form>';
echo '<p>'.$t('count').': '.$e($reportData['total']).'</p><div class="chisimba-form-actions"><a class="button chisimba-button-secondary" href="'.$url(['format'=>'csv']).'">'.$t('csv').'</a></div>';
$columns = $reportSummary ? ['username','fullname','accesses'] : ['username','fullname','title','pageorchapter','starttime','endtime'];
echo '<div class="chisimba-table-wrap" tabindex="0"><table class="chisimba-table"><thead><tr>';
foreach ($columns as $key) echo '<th scope="col">'.$t($key).'</th>';
echo '</tr></thead><tbody>';
foreach ($reportData['rows'] as $row) { echo '<tr>'; foreach ($columns as $key) echo '<td>'.$e($row[$key] ?? '').'</td>'; echo '</tr>'; }
if (!$reportData['rows']) echo '<tr><td colspan="'.count($columns).'">'.$t('empty').'</td></tr>';
echo '</tbody></table></div><nav class="chisimba-form-actions" aria-label="'.$t('pages').'">';
if ($reportData['page'] > 1) echo '<a class="button chisimba-button-secondary" href="'.$url(['page'=>$reportData['page']-1]).'">'.$t('previous').'</a>';
echo '<span>'.$t('page').' '.$e($reportData['page']).'</span>';
if ($reportData['page'] * 30 < $reportData['total']) echo '<a class="button chisimba-button-secondary" href="'.$url(['page'=>$reportData['page']+1]).'">'.$t('next').'</a>';
echo '</nav></section>';
