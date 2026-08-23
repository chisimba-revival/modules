<?php
$esc = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
$icon = static function ($name) use ($icons) { return $icons->render($name, array('decorative'=>true, 'class'=>'chisimba-action-icon')); };
$url = function ($params) { return $this->uriForHtmlAttribute($params, 'rubric'); };
$action = $url(array('action'=>'renametableconfirm', 'tableId'=>$tableId));
echo '<main class="rubric-form"><header class="rubric-page-header"><div><h1>'.$esc($objLanguage->languageText('rubric_renamerubric','rubric')).'</h1>'
    .'<p>'.$esc($objLanguage->languageText('mod_rubric_rename_intro','rubric')).'</p></div></header>'
    .'<form method="post" action="'.$action.'" class="rubric-form-card">'
    .'<div class="rubric-form-field"><label for="rubric-title">'.$esc($objLanguage->languageText('rubric_title','rubric')).'</label>'
    .'<input id="rubric-title" name="title" type="text" required maxlength="255" value="'.$esc($title).'"></div>'
    .'<div class="rubric-form-field"><label for="rubric-description">'.$esc($objLanguage->languageText('rubric_description','rubric')).'</label>'
    .'<textarea id="rubric-description" name="description" rows="4" required>'.$esc($description).'</textarea></div>'
    .'<div class="rubric-form-actions"><button class="button" type="submit">'.$icon('circle-check').'<span>'.$esc($objLanguage->languageText('word_save')).'</span></button>'
    .'<a class="button chisimba-button-secondary" href="'.$url(array()).'">'.$icon('x').'<span>'.$esc($objLanguage->languageText('word_cancel')).'</span></a>'
    .'</div></form></main>';
?>
