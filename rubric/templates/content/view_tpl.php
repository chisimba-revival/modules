<?php
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
$icon = static function ($name) use ($icons) { return $icons->render($name, array('decorative'=>true, 'class'=>'chisimba-action-icon')); };
$url = function ($params) { return $this->uriForHtmlAttribute($params, 'rubric'); };
if ($this->getParam('saved', '') === 'yes') {
    echo '<div class="rubric-notice" role="status">'.$esc($objLanguage->languageText('mod_rubric_saved', 'rubric')).'</div>';
}
echo '<article class="rubric-view"><header class="rubric-view-header"><h1>'.$esc($title).'</h1><p>'.$esc($description).'</p></header>';
if (isset($IsAssessment)) {
    echo '<dl class="rubric-form-meta"><div><dt><strong>'.$esc(ucfirst($objLanguage->code2Txt('rubric_teacher','rubric'))).'</strong></dt><dd>'.$esc($teacher).'</dd></div>'
        .'<div><dt><strong>'.$esc(ucfirst($objLanguage->code2Txt('rubric_student','rubric'))).'</strong></dt><dd>'.$esc($student).' ('.$esc($studentNo).')</dd></div>'
        .'<div><dt><strong>'.$esc($objLanguage->languageText('rubric_datesubmitted','rubric')).'</strong></dt><dd>'.$esc($date).'</dd></div></dl>';
}
echo '<div class="rubric-matrix rubric-matrix-scroll"><table><thead><tr><th scope="col">'.$esc($objLanguage->languageText('mod_rubric_criteria','rubric')).'</th>';
for ($j = 0; $j < $cols; $j++) { echo '<th scope="col">'.$esc($performances[$j]).'</th>'; }
if (isset($IsAssessment)) { echo '<th scope="col">'.$esc($objLanguage->languageText('rubric_score','rubric')).'</th>'; }
echo '</tr></thead><tbody>';
for ($i = 0; $i < $rows; $i++) {
    echo '<tr><th scope="row">'.$esc($objectives[$i]).'</th>';
    for ($j = 0; $j < $cols; $j++) { echo '<td>'.$esc($cells[$i][$j]).'</td>'; }
    if (isset($IsAssessment)) { echo '<td>'.$esc($scores[$i]).'</td>'; }
    echo '</tr>';
}
if (isset($IsAssessment)) {
    echo '<tr><th scope="row" colspan="'.($cols).'">'.$esc($objLanguage->languageText('rubric_total','rubric')).'</th><td>'.$esc($total.'/'.$maxtotal).'</td></tr>';
}
echo '</tbody></table></div><nav class="rubric-view-actions" aria-label="'.$esc($objLanguage->languageText('mod_rubric_actions','rubric')).'">';
if ($noBanner === 'yes') {
    echo '<button class="button chisimba-button-secondary" type="button" onclick="history.back()">'.$icon('chevron-left').'<span>'.$esc($objLanguage->languageText('word_back')).'</span></button>';
} else {
    echo '<button class="button chisimba-button-secondary" type="button" onclick="window.print()">'.$icon('file-text').'<span>'.$esc($objLanguage->languageText('word_print')).'</span></button>';
    $back = isset($IsAssessment)
        ? $url(array('action'=>'assessments','tableId'=>$tableId))
        : $url(array());
    echo '<a class="button chisimba-button-secondary" href="'.$back.'">'.$icon('chevron-left').'<span>'.$esc($objLanguage->languageText('word_back')).'</span></a>';
}
echo '</nav></article>';
?>
