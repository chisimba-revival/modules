<?php
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
$icon = static function ($name) use ($icons) {
    return $icons->render($name, array('decorative'=>true, 'class'=>'chisimba-action-icon'));
};
$error = $this->getParam('error', '');
if ($error === 'matrixmissing' || $error === 'savefailed') {
    $errorKey = $error === 'matrixmissing' ? 'mod_rubric_matrix_missing' : 'mod_rubric_save_failed';
    echo '<div class="rubric-notice error" role="alert">'.$esc($objLanguage->languageText($errorKey, 'rubric')).'</div>';
}
$action = $this->uri(array(
    'module'=>'rubric', 'action'=>'edittableconfirm', 'tableId'=>$tableId,
    'returnModule'=>$this->getParam('returnModule', ''),
    'returnAction'=>$this->getParam('returnAction', ''),
    'returnId'=>$this->getParam('returnId', '')
));
echo '<header class="rubric-editor-header"><h1>'.$esc($objLanguage->languageText('mod_rubric_edit_heading', 'rubric')).': '.$esc($title).'</h1>'
    .'<p>'.$esc($description).'</p></header>';
echo '<details class="rubric-editor-help"'.($this->getParam('new') === 'yes' ? ' open' : '').'><summary>'
    .$esc($objLanguage->languageText('mod_rubric_editor_help', 'rubric')).'</summary><ol>';
foreach (array('mod_rubric_instructionpart1','mod_rubric_instructionpart2','mod_rubric_instructionpart3','mod_rubric_instructionpart4') as $instruction) {
    echo '<li>'.$esc($objLanguage->languageText($instruction, 'rubric')).'</li>';
}
echo '</ol></details><form class="rubric-editor-form" method="post" action="'.$esc($action).'">'
    .'<div class="rubric-matrix rubric-matrix-scroll"><table><thead><tr><th scope="col">'
    .'<span class="rubric-visually-hidden">'.$esc($objLanguage->languageText('mod_rubric_criteria', 'rubric')).'</span></th>';
for ($j = 0; $j < $cols; $j++) {
    $id = 'performance'.$j;
    echo '<th scope="col"><label for="'.$id.'">'.$esc($objLanguage->languageText('mod_rubric_level', 'rubric')).' '.($j + 1).'</label>'
        .'<textarea id="'.$id.'" name="'.$id.'" rows="2">'.$esc($performances[$j]).'</textarea></th>';
}
echo '</tr></thead><tbody>';
for ($i = 0; $i < $rows; $i++) {
    $objectiveId = 'objective'.$i;
    echo '<tr><th scope="row"><label for="'.$objectiveId.'">'.$esc($objLanguage->languageText('mod_rubric_criterion', 'rubric')).' '.($i + 1).'</label>'
        .'<textarea id="'.$objectiveId.'" name="'.$objectiveId.'" rows="3">'.$esc($objectives[$i]).'</textarea></th>';
    for ($j = 0; $j < $cols; $j++) {
        $cellId = 'cell-'.$i.'-'.$j;
        $cellName = 'cell'.$i.'['.$j.']';
        $label = $objLanguage->languageText('mod_rubric_descriptor', 'rubric').' '.($i + 1).', '.($j + 1);
        echo '<td><label class="rubric-visually-hidden" for="'.$cellId.'">'.$esc($label).'</label>'
            .'<textarea id="'.$cellId.'" name="'.$esc($cellName).'" rows="5">'.$esc($cells[$i][$j]).'</textarea></td>';
    }
    echo '</tr>';
}
echo '</tbody></table></div><div class="rubric-editor-actions"><button class="button" type="submit">'
    .$icon('circle-check').'<span>'.$esc($objLanguage->languageText('word_save')).'</span></button>'
    .'<a class="button chisimba-button-secondary" href="'.$esc($this->uri(array('module'=>'rubric','action'=>'viewtable','tableId'=>$tableId))).'">'
    .$icon('eye').'<span>'.$esc($objLanguage->languageText('word_view')).'</span></a></div></form>';
if (!isset($suppressModify)) {
    echo '<nav class="rubric-structure-actions" aria-label="'.$esc($objLanguage->languageText('mod_rubric_structure_actions', 'rubric')).'">'
        .'<a class="button chisimba-button-secondary" href="'.$esc($this->uri(array('action'=>'addrow','tableId'=>$tableId))).'">'.$icon('plus').'<span>'.$esc($objLanguage->languageText('rubric_add_row', 'rubric')).'</span></a>'
        .'<a class="button chisimba-button-secondary" href="'.$esc($this->uri(array('action'=>'addcol','tableId'=>$tableId))).'">'.$icon('plus').'<span>'.$esc($objLanguage->languageText('rubric_add_column', 'rubric')).'</span></a>';
    if ($rows > 1) {
        echo '<a class="button rubric-button-danger" href="'.$esc($this->uri(array('action'=>'delrow','tableId'=>$tableId))).'">'.$icon('minus').'<span>'.$esc($objLanguage->languageText('rubric_delete_row', 'rubric')).'</span></a>';
    }
    if ($cols > 1) {
        echo '<a class="button rubric-button-danger" href="'.$esc($this->uri(array('action'=>'delcol','tableId'=>$tableId))).'">'.$icon('minus').'<span>'.$esc($objLanguage->languageText('rubric_delete_column', 'rubric')).'</span></a>';
    }
    echo '</nav>';
}
?>
