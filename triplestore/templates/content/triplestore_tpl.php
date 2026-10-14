<?php
/** Native relationship browser. @author Derek Keats */
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $e($this->objLanguage->languageText('mod_triplestore_'.$key, 'triplestore'));
$url = fn($params) => $e($this->uri($params, 'triplestore'));
echo '<section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$t('title').'</h1><p>'.$t('intro').'</p>';
echo '<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="'.$url(['action'=>'add']).'">'.$t('add').'</a>';
echo $this->getObject('contextualhelp','help')->show('triplestore','manage',true).'</div>';
if ($notice) echo '<p role="status" class="chisimba-notice">'.$t($notice).'</p>';
echo '<form method="get" action="'.$url([]).'" class="chisimba-form-grid"><input type="hidden" name="module" value="triplestore"><input type="hidden" name="action" value="search">';
foreach (['subject','predicate','object'] as $field) {
    echo '<div class="chisimba-form-field"><label for="filter-'.$field.'">'.$t($field).'</label><input type="text" id="filter-'.$field.'" name="'.$field.'" value="'.$e($filters[$field] ?? '').'"></div>';
}
foreach (['id','userid'] as $field) if (isset($filters[$field])) echo '<input type="hidden" name="'.$field.'" value="'.$e($filters[$field]).'">';
echo '<div class="chisimba-form-actions"><button type="submit">'.$t('filter').'</button><a class="button chisimba-button-secondary" href="'.$url([]).'">'.$t('reset').'</a></div></form>';
echo '<p>'.$t('exact').' '.$t('count').': '.$e($data['total']).'</p>';
echo '<div class="chisimba-table-wrap" tabindex="0" role="region" aria-label="'.$t('triples').'"><table class="chisimba-table"><thead><tr>';
foreach (['subject','predicate','object','actions'] as $field) echo '<th scope="col">'.$t($field).'</th>';
echo '</tr></thead><tbody>';
foreach ($data['rows'] as $row) {
    echo '<tr>';
    foreach (['subject','predicate','object'] as $field) echo '<td>'.$e($row[$field]).'</td>';
    echo '<td><div class="chisimba-cluster"><a class="button chisimba-button-secondary" href="'.$url(['action'=>'edit','id'=>$row['id']]).'">'.$t('edit').'</a><a class="button chisimba-button-danger" href="'.$url(['action'=>'confirmdelete','id'=>$row['id']]).'">'.$t('delete').'</a></div></td></tr>';
}
if (!$data['rows']) echo '<tr><td colspan="4">'.$t('empty').'</td></tr>';
echo '</tbody></table></div><nav class="chisimba-form-actions" aria-label="'.$t('pages').'">';
if ($data['page'] > 1) echo '<a class="button chisimba-button-secondary" href="'.$url(array_merge($filters,['page'=>$data['page']-1])).'">'.$t('previous').'</a>';
echo '<span>'.$t('page').' '.$e($data['page']).'</span>';
if ($data['page'] * $data['size'] < $data['total']) echo '<a class="button chisimba-button-secondary" href="'.$url(array_merge($filters,['page'=>$data['page']+1])).'">'.$t('next').'</a>';
echo '</nav></section>';
