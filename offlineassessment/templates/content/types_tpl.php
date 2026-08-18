<?php
$language = $this->objLanguage;
$L = function ($k) use ($language) {
    return $language->languageText('mod_offlineassessment_'.$k, 'offlineassessment');
};
$esc = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

echo '<div class="chisimba-workspace">';
echo '<h1>'.$esc($L('types')).'</h1>';
echo '<div class="chisimba-editor-list">';

$count = count($types);
foreach ($types as $i => $t) {
    echo '<form class="chisimba-editor-row" method="post" action="'.$this->uri(array('action'=>'savetype')).'">'
        .'<input type="hidden" name="id" value="'.$esc($t['id']).'">'
        .'<input type="hidden" name="sort_order" value="'.(int)$t['sort_order'].'">'
        .'<div class="chisimba-editor-main"><label><span>'.$esc($L('typename')).'</span>'
        .'<input name="name" required value="'.$esc($t['name']).'"></label></div>'
        .'<div class="chisimba-editor-status"><label><span>'.$esc($L('status')).'</span><select name="status">'
        .'<option value="active">'.$esc($L('active')).'</option>'
        .'<option value="inactive"'.($t['status']==='inactive'?' selected':'').'>'.$esc($L('inactive')).'</option>'
        .'</select></label></div>'
        .'<div class="chisimba-editor-order" aria-label="'.$esc($L('sortorder')).'">'
        .($i>0 ? '<a class="chisimba-icon-button" title="'.$esc($L('moveup')).'" href="'.$this->uri(array('action'=>'movetype','id'=>$t['id'],'direction'=>'up')).'">↑</a>' : '<span class="chisimba-icon-button-placeholder"></span>')
        .($i<$count-1 ? '<a class="chisimba-icon-button" title="'.$esc($L('movedown')).'" href="'.$this->uri(array('action'=>'movetype','id'=>$t['id'],'direction'=>'down')).'">↓</a>' : '<span class="chisimba-icon-button-placeholder"></span>')
        .'</div>'
        .'<button type="submit">'.$esc($L('save')).'</button>'
        .'</form>';
}
echo '</div>';

echo '<section class="chisimba-form-section"><h2>'.$esc($L('addtype')).'</h2>'
    .'<form class="chisimba-editor-row chisimba-editor-row--new" method="post" action="'.$this->uri(array('action'=>'savetype')).'">'
    .'<div class="chisimba-editor-main"><label><span>'.$esc($L('typename')).'</span><input required name="name"></label></div>'
    .'<input type="hidden" name="sort_order" value="'.($count+1).'">'
    .'<button type="submit">'.$esc($L('save')).'</button></form></section>';

echo '<p><a href="'.$this->uri(array()).'">← '.$esc($L('backtoassessments')).'</a></p>';
echo '</div>';
?>
