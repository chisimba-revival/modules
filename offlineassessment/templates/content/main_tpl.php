<?php
$language = $this->objLanguage;
$L = function ($k) use ($language) {
    return $language->languageText('mod_offlineassessment_'.$k, 'offlineassessment');
};
$esc = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$typeNames = array();
foreach ((array)$types as $t) { $typeNames[$t['id']] = $t['name']; }

$objIcon = $this->getObject('iconservice', 'ui');
$icon = function ($name) use ($objIcon) {
    return $objIcon->render($name, array('decorative'=>TRUE, 'class'=>'chisimba-action-icon'));
};

echo '<div class="chisimba-workspace">';
echo '<h1>'.$esc($L('title')).'</h1>';
echo '<div class="chisimba-actions">'
    .'<a class="button" href="'.$this->uri(array('action'=>'edit')).'">'.$esc($L('new')).'</a>'
    .'<a class="button" href="'.$this->uri(array('action'=>'types')).'">'.$esc($L('types')).'</a>'
    .'</div>';

if (empty($assessments)) {
    echo '<p>'.$esc($L('noassessments')).'</p></div>';
    return;
}

echo '<div class="chisimba-table-wrap"><table class="chisimba-table">'
    .'<thead><tr>'
    .'<th>'.$esc($L('assessmentname')).'</th>'
    .'<th>'.$esc($L('type')).'</th>'
    .'<th>'.$esc($L('classification')).'</th>'
    .'<th>'.$esc($L('maxmark')).'</th>'
    .'<th class="chisimba-table-actions">'.$esc($L('actions')).'</th>'
    .'</tr></thead><tbody>';

foreach ($assessments as $a) {
    $max = rtrim(rtrim(number_format((float)$a['maximum_mark'], 3, '.', ''), '0'), '.');
    echo '<tr>'
        .'<td>'.$esc($a['name']).'</td>'
        .'<td>'.$esc(isset($typeNames[$a['type_id']]) ? $typeNames[$a['type_id']] : '').'</td>'
        .'<td>'.$esc($L($a['classification'])).'</td>'
        .'<td>'.$esc($max).'</td>'
        .'<td class="chisimba-table-actions"><div class="chisimba-row-actions">'
        .'<a href="'.$this->uri(array('action'=>'marks','id'=>$a['id'])).'">'.$icon('circle-check').'<span>'.$esc($L('entermarks')).'</span></a>'
        .'<a href="'.$this->uri(array('action'=>'edit','id'=>$a['id'])).'">'.$icon('pencil').'<span>'.$esc($L('edit')).'</span></a>'
        .'<a href="'.$this->uri(array('action'=>'audit','id'=>$a['id'])).'">'.$icon('clock').'<span>'.$esc($L('audit')).'</span></a>'
        .'</div></td></tr>';
}
echo '</tbody></table></div></div>';
?>
