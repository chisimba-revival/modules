<?php
$language = $this->objLanguage;
$L = function ($k) use ($language) {
    return $language->languageText('mod_offlineassessment_'.$k, 'offlineassessment');
};
$typeNames=array(); foreach((array)$types as $t)$typeNames[$t['id']]=$t['name'];
echo '<h1>'.htmlspecialchars($L('title')).'</h1>';
echo '<p><a class="button" href="'.$this->uri(array('action'=>'edit')).'">'.htmlspecialchars($L('new')).'</a> &nbsp; <a href="'.$this->uri(array('action'=>'types')).'">'.htmlspecialchars($L('types')).'</a></p>';
if(empty($assessments)){echo '<p>'.htmlspecialchars($L('noassessments')).'</p>';return;}
echo '<table class="table"><thead><tr><th>'.$L('assessmentname').'</th><th>'.$L('type').'</th><th>'.$L('classification').'</th><th>'.$L('maxmark').'</th><th>'.$L('actions').'</th></tr></thead><tbody>';
foreach($assessments as $a){ echo '<tr><td>'.htmlspecialchars($a['name']).'</td><td>'.htmlspecialchars($typeNames[$a['type_id']]??'').'</td><td>'.htmlspecialchars($L($a['classification'])).'</td><td>'.htmlspecialchars($a['maximum_mark']).'</td><td><a href="'.$this->uri(array('action'=>'marks','id'=>$a['id'])).'">'.$L('entermarks').'</a> &nbsp; <a href="'.$this->uri(array('action'=>'edit','id'=>$a['id'])).'">'.$L('edit').'</a> &nbsp; <a href="'.$this->uri(array('action'=>'audit','id'=>$a['id'])).'">'.$L('audit').'</a></td></tr>';}
echo '</tbody></table>';
?>