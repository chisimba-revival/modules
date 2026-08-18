<?php
$language = $this->objLanguage;
$L = function ($k) use ($language) {
    return $language->languageText('mod_offlineassessment_'.$k, 'offlineassessment');
};
$a = (array)$assessment;
function oaVal($a,$k,$d=''){return htmlspecialchars(isset($a[$k])?$a[$k]:$d,ENT_QUOTES,'UTF-8');}
$esc = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$isActive = (($a['status'] ?? 'active') !== 'inactive');

echo '<div class="chisimba-workspace chisimba-form-workspace">';
echo '<h1>'.$esc(empty($a)?$L('new'):$L('edit')).'</h1>';
echo '<form class="chisimba-form" method="post" action="'.$this->uri(array('action'=>'save')).'">';
echo '<input type="hidden" name="id" value="'.oaVal($a,'id').'">';

echo '<div class="chisimba-form-field"><label for="oa-name">'.$esc($L('assessmentname')).'</label>'
    .'<input id="oa-name" required type="text" name="name" value="'.oaVal($a,'name').'"></div>';

echo '<div class="chisimba-form-field"><label for="oa-type">'.$esc($L('type')).'</label>'
    .'<select id="oa-type" required name="type_id"><option value="">'.$esc($L('selecttype')).'</option>';
foreach($types as $t){
    $sel=(($a['type_id']??'')===$t['id'])?' selected':'';
    echo '<option value="'.$esc($t['id']).'"'.$sel.'>'.$esc($t['name']).'</option>';
}
echo '</select></div>';

echo '<fieldset class="chisimba-form-field chisimba-choice-field"><legend>'.$esc($L('classification')).'</legend>'
    .'<div class="chisimba-choice-group">'
    .'<label><input type="radio" name="classification" value="formative"'.(($a['classification']??'')==='formative'?' checked':'').'> '.$esc($L('formative')).'</label>'
    .'<label><input type="radio" name="classification" value="summative"'.(($a['classification']??'summative')==='summative'?' checked':'').'> '.$esc($L('summative')).'</label>'
    .'</div></fieldset>';

echo '<div class="chisimba-form-field chisimba-form-field--compact"><label for="oa-max">'.$esc($L('maxmark')).'</label>'
    .'<input id="oa-max" required min="0.001" step="0.001" type="number" name="maximum_mark" value="'.oaVal($a,'maximum_mark','100').'"></div>';

echo '<div class="chisimba-form-field chisimba-form-field--compact"><label for="oa-date">'.$esc($L('date')).'</label>'
    .'<input id="oa-date" type="date" name="assessment_date" value="'.oaVal($a,'assessment_date').'"></div>';

echo '<div class="chisimba-form-field"><label for="oa-description">'.$esc($L('description')).'</label>'
    .'<textarea id="oa-description" name="description" rows="5">'.oaVal($a,'description').'</textarea></div>';

echo '<fieldset class="chisimba-form-field chisimba-choice-field"><legend>'.$esc($L('status')).'</legend>'
    .'<div class="chisimba-choice-group">'
    .'<label><input type="radio" name="status" value="active"'.($isActive?' checked':'').'> '.$esc($L('active')).'</label>'
    .'<label><input type="radio" name="status" value="inactive"'.(!$isActive?' checked':'').'> '.$esc($L('inactive')).'</label>'
    .'</div></fieldset>';

echo '<div class="chisimba-form-actions"><button type="submit">'.$esc($L('save')).'</button>'
    .'<a class="button chisimba-button-secondary" href="'.$this->uri(array()).'">'.$esc($L('cancel')).'</a></div>';
echo '</form></div>';
?>
