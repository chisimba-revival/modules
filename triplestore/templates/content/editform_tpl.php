<?php
/** Native editor and explicit delete confirmation. @author Derek Keats */
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $e($this->objLanguage->languageText('mod_triplestore_'.$key, 'triplestore'));
$url = fn($params) => $e($this->uri($params, 'triplestore'));
echo '<section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$t($deleting ? 'confirmdelete' : (empty($triple['id']) ? 'add' : 'edit')).'</h1>';
echo $this->getObject('contextualhelp','help')->show('triplestore','manage',true);
if ($notice) echo '<p class="chisimba-notice" role="alert">'.$t($notice).'</p>';
if ($notice === 'conflict' || $notice === 'failed') echo '<p><a href="'.$url(empty($triple['id']) ? [] : ['action'=>'edit','id'=>$triple['id']]).'">'.$t('review').'</a></p>';
echo '<form method="post" action="'.$url(['action'=>$deleting ? 'delete' : 'save']).'" class="chisimba-flow">';
foreach (['id'=>$triple['id'] ?? '', 'csrf_token'=>$token, 'revision'=>$revision] as $name=>$value) echo '<input type="hidden" name="'.$name.'" value="'.$e($value).'">';
if ($deleting) echo '<p>'.$t('deletewarning').'</p>';
foreach (['subject'=>32,'predicate'=>32,'object'=>16384] as $field=>$limit) {
    echo '<div class="chisimba-form-field"><label for="triple-'.$field.'">'.$t($field).'</label>';
    if ($field === 'object') echo '<textarea id="triple-'.$field.'" name="'.$field.'" rows="5" maxlength="'.$limit.'" required'.($deleting ? ' readonly' : '').'>'.$e($triple[$field] ?? '').'</textarea>';
    else echo '<input type="text" id="triple-'.$field.'" name="'.$field.'" maxlength="'.$limit.'" required value="'.$e($triple[$field] ?? '').'"'.($deleting ? ' readonly' : '').'>';
    echo '</div>';
}
echo '<div class="chisimba-form-actions"><button type="submit"'.($deleting ? ' class="chisimba-button-danger"' : '').'>'.$t($deleting ? 'delete' : 'save').'</button><a class="button chisimba-button-secondary" href="'.$url([]).'">'.$t('cancel').'</a></div></form></section>';
