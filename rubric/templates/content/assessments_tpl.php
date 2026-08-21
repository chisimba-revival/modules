<?php
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
$icon = static function ($name) use ($icons) { return $icons->render($name, array('decorative'=>true, 'class'=>'chisimba-action-icon')); };
$url = function ($params) { return $this->uriForHtmlAttribute($params, 'rubric'); };
$canAdd = $this->isValid('addassessment');
echo '<main class="rubric-workspace"><header class="rubric-page-header"><div><h1>'.$esc($title).'</h1><p>'.$esc($description).'</p></div>';
if ($canAdd) {
    echo '<a class="button" href="'.$url(array('action'=>'addassessment','tableId'=>$tableId)).'">'.$icon('plus').'<span>'.$esc($objLanguage->languageText('rubric_addassessment','rubric')).'</span></a>';
}
echo '</header><div class="chisimba-table-wrap"><table class="chisimba-table"><thead><tr><th scope="col">'
    .$esc(ucfirst($objLanguage->code2Txt('word_username','system')).' / '.ucfirst($objLanguage->code2Txt('rubric_studentno','rubric'))).'</th>';
if ($showStudentNames === 'yes') { echo '<th scope="col">'.$esc($objLanguage->languageText('rubric_name','rubric')).'</th>'; }
echo '<th scope="col">'.$esc($objLanguage->languageText('rubric_score','rubric')).'</th><th scope="col">'.$esc(ucfirst($objLanguage->code2Txt('rubric_teacher','rubric'))).'</th>'
    .'<th scope="col">'.$esc($objLanguage->languageText('rubric_date','rubric')).'</th><th scope="col" class="rubric-actions-heading">'.$esc($objLanguage->languageText('mod_rubric_actions','rubric')).'</th></tr></thead><tbody>';
$shown = 0;
foreach ($assessments as $assessment) {
    $canView = $this->isValid('viewassessment') && (
        $this->objUser->isContextLecturer($this->objUser->userId(), $this->contextCode)
        || ($this->objUser->isContextStudent($this->contextCode) && $this->objUser->userName() === $assessment['studentno'])
    );
    if (!$canView) { continue; }
    $shown++;
    $scoresList = explode(',', $assessment['scores']);
    $total = array_sum(array_map('intval', $scoresList));
    $view = $url(array('action'=>'viewassessment','tableId'=>$tableId,'id'=>$assessment['id']));
    echo '<tr><th scope="row"><a href="'.$view.'">'.$esc($assessment['studentno']).'</a></th>';
    if ($showStudentNames === 'yes') { echo '<td>'.$esc($assessment['student']).'</td>'; }
    echo '<td>'.$esc($total.'/'.$maxtotal).'</td><td>'.$esc($assessment['teacher']).'</td><td>'.$esc($assessment['timestamp']).'</td><td><div class="rubric-row-actions">';
    if ($this->isValid('editassessment')) {
        echo '<a class="button chisimba-button-secondary" href="'.$url(array('action'=>'editassessment','tableId'=>$tableId,'id'=>$assessment['id'])).'">'.$icon('pencil').'<span>'.$esc($objLanguage->languageText('word_edit')).'</span></a>';
    }
    if ($this->isValid('deleteassessment')) {
        $confirm = $esc(json_encode($objLanguage->languageText('mod_rubric_suredeleteassessment','rubric'), JSON_HEX_APOS | JSON_HEX_QUOT));
        echo '<a class="button rubric-button-danger" onclick="return confirm('.$confirm.')" href="'.$url(array('action'=>'deleteAssessment','tableId'=>$tableId,'id'=>$assessment['id'])).'">'.$icon('trash-2').'<span>'.$esc($objLanguage->languageText('word_delete')).'</span></a>';
    }
    echo '</div></td></tr>';
}
if ($shown === 0) { echo '<tr><td colspan="'.($showStudentNames === 'yes' ? 6 : 5).'"><div class="rubric-empty">'.$esc($objLanguage->languageText('mod_rubric_norecords','rubric')).'</div></td></tr>'; }
echo '</tbody></table></div><nav class="rubric-view-actions" aria-label="'.$esc($objLanguage->languageText('mod_rubric_actions','rubric')).'">';
if ($this->objUser->isContextLecturer()) {
    $toggle = $showStudentNames === 'yes' ? 'no' : 'yes';
    $toggleLabel = $showStudentNames === 'yes' ? $objLanguage->languageText('rubric_hide','rubric') : $objLanguage->languageText('rubric_show','rubric');
    echo '<a class="button chisimba-button-secondary" href="'.$url(array('action'=>'assessments','tableId'=>$tableId,'showStudentNames'=>$toggle)).'">'.$icon('users-round').'<span>'.$esc($toggleLabel).'</span></a>'
        .'<button class="button chisimba-button-secondary" type="button" onclick="window.print()">'.$icon('file-text').'<span>'.$esc($objLanguage->languageText('word_print')).'</span></button>';
}
echo '<a class="button chisimba-button-secondary" href="'.$url(array()).'">'.$icon('chevron-left').'<span>'.$esc($objLanguage->languageText('word_back')).'</span></a></nav></main>';
?>
