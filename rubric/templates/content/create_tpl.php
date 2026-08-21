<?php
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
$icon = static function ($name) use ($icons) {
    return $icons->render($name, array('decorative'=>true, 'class'=>'chisimba-action-icon'));
};
$returnModule = $this->getParam('returnModule', '');
$returnAction = $this->getParam('returnAction', '');
$returnId = $this->getParam('returnId', '');
$action = $this->uri(array(
    'module'=>'rubric', 'action'=>'createtableconfirm', 'type'=>$_type,
    'returnModule'=>$returnModule, 'returnAction'=>$returnAction, 'returnId'=>$returnId
));
$cancelUrl = ($returnModule === 'worksheet' && in_array($returnAction, array('editquestion', 'managequestions')) && $returnId !== '')
    ? $this->uri(array('module'=>'worksheet', 'action'=>$returnAction, 'id'=>$returnId))
    : $this->uri(array('module'=>'rubric'));
echo '<main class="rubric-form"><header class="rubric-page-header"><div><h1>'.$esc($objLanguage->languageText('rubric_createrubric','rubric')).'</h1>'
    .'<p>'.$esc($objLanguage->languageText('mod_rubric_create_intro','rubric')).'</p></div></header>'
    .'<form method="post" action="'.$esc($action).'" class="rubric-form-card">'
    .'<div class="rubric-form-meta"><div><strong>'.$esc($objLanguage->languageText('rubric_name','rubric')).'</strong>'.$esc($objUser->fullName()).'</div>'
    .'<div><strong>'.$esc($objLanguage->languageText('rubric_resource_scope','rubric')).'</strong>'.$esc($rubricScope).'</div></div>'
    .'<div class="rubric-form-field"><label for="rubric-title">'.$esc($objLanguage->languageText('rubric_title','rubric')).'</label>'
    .'<input id="rubric-title" name="title" type="text" required maxlength="255" autocomplete="off"></div>'
    .'<div class="rubric-form-field"><label for="rubric-description">'.$esc($objLanguage->languageText('rubric_description','rubric')).'</label>'
    .'<textarea id="rubric-description" name="description" rows="4" required></textarea></div>'
    .'<div class="rubric-form-meta"><div class="rubric-form-field"><label for="rubric-rows">'.$esc($objLanguage->languageText('word_objectives','rubric')).'</label>'
    .'<select id="rubric-rows" name="rows">';
for ($number = 1; $number <= 9; $number++) { echo '<option value="'.$number.'">'.$number.'</option>'; }
echo '</select></div><div class="rubric-form-field"><label for="rubric-cols">'.$esc($objLanguage->languageText('word_performance','rubric')).'</label>'
    .'<select id="rubric-cols" name="cols">';
for ($number = 1; $number <= 9; $number++) { echo '<option value="'.$number.'">'.$number.'</option>'; }
echo '</select></div></div><div class="rubric-form-actions"><button class="button" type="submit">'.$icon('plus').'<span>'
    .$esc($objLanguage->languageText('word_create', 'system', 'Create')).'</span></button>'
    .'<a class="button chisimba-button-secondary" href="'.$esc($cancelUrl).'">'.$icon('x').'<span>'.$esc($objLanguage->languageText('word_cancel')).'</span></a>'
    .'</div></form></main>';
?>
