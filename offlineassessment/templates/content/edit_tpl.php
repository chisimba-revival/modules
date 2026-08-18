<?php
$L=function($k)use($this){return $this->objLanguage->languageText('mod_offlineassessment_'.$k,'offlineassessment');}; $a=(array)$assessment;
function oaVal($a,$k,$d=''){return htmlspecialchars(isset($a[$k])?$a[$k]:$d,ENT_QUOTES,'UTF-8');}
echo '<h1>'.htmlspecialchars(empty($a)?$L('new'):$L('edit')).'</h1><form method="post" action="'.$this->uri(array('action'=>'save')).'">';
echo '<input type="hidden" name="id" value="'.oaVal($a,'id').'">';
echo '<p><label>'.$L('name').'<br><input required type="text" name="name" value="'.oaVal($a,'name').'" size="60"></label></p>';
echo '<p><label>'.$L('type').'<br><select required name="type_id"><option value="">'.$L('selecttype').'</option>'; foreach($types as $t){$sel=(($a['type_id']??'')===$t['id'])?' selected':'';echo '<option value="'.htmlspecialchars($t['id']).'"'.$sel.'>'.htmlspecialchars($t['name']).'</option>';} echo '</select></label></p>';
echo '<p><label>'.$L('classification').'<br><select name="classification"><option value="formative"'.(($a['classification']??'')==='formative'?' selected':'').'>'.$L('formative').'</option><option value="summative"'.(($a['classification']??'summative')==='summative'?' selected':'').'>'.$L('summative').'</option></select></label></p>';
echo '<p><label>'.$L('maxmark').'<br><input required min="0.001" step="0.001" type="number" name="maximum_mark" value="'.oaVal($a,'maximum_mark','100').'"></label></p>';
echo '<p><label>'.$L('date').'<br><input type="date" name="assessment_date" value="'.oaVal($a,'assessment_date').'" ></label></p>';
echo '<p><label>'.$L('description').'<br><textarea name="description" rows="5" cols="70">'.oaVal($a,'description').'</textarea></label></p>';
echo '<p><label>'.$L('status').' <select name="status"><option value="active">'.$L('active').'</option><option value="inactive"'.(($a['status']??'')==='inactive'?' selected':'').'>'.$L('inactive').'</option></select></label></p>';
echo '<p><button type="submit">'.$L('save').'</button> <a href="'.$this->uri(array()).'">'.$L('cancel').'</a></p></form>';
?>