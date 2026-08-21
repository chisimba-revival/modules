<?php
$esc = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$lang = static function ($key, $module = 'rubric', $default = null) use ($objLanguage) {
    return $objLanguage->languageText($key, $module, $default);
};
$icons = $this->getObject('iconservice', 'ui');
$icon = static function ($name) use ($icons) {
    return $icons->render($name, array('decorative'=>true, 'class'=>'chisimba-action-icon'));
};
$url = function ($params) { return $this->uriForHtmlAttribute($params, 'rubric'); };
$button = static function ($url, $label, $iconName, $class = 'chisimba-button-secondary', $confirm = '') use ($esc, $icon) {
    $confirmAttribute = $confirm === '' ? '' : ' onclick="return confirm('.$esc(json_encode($confirm, JSON_HEX_APOS | JSON_HEX_QUOT)).')"';
    return '<a class="button '.$class.'" href="'.$url.'"'.$confirmAttribute.'>'.$icon($iconName).'<span>'.$esc($label).'</span></a>';
};

$renderSection = function ($heading, $description, $records, $scope, $create = '') use ($esc, $lang, $button, $url, $contextCode) {
    $out = '<section class="rubric-library-section" aria-labelledby="rubric-'.$esc($scope).'-heading">'
        .'<div class="rubric-section-heading"><div><h2 id="rubric-'.$esc($scope).'-heading">'.$esc($heading).'</h2>'
        .'<p>'.$esc($description).'</p></div>'.$create.'</div>';
    if (empty($records)) {
        return $out.'<div class="rubric-empty"><h3>'.$esc($lang('mod_rubric_empty_heading')).'</h3><p>'.$esc($lang('mod_rubric_empty_description')).'</p></div></section>';
    }
    $out .= '<div class="chisimba-table-wrap"><table class="chisimba-table rubric-library-table"><thead><tr>'
        .'<th scope="col">'.$esc($lang('word_title', 'system', 'Title')).'</th>'
        .'<th scope="col">'.$esc($lang('rubric_description')).'</th>'
        .'<th scope="col" class="rubric-actions-heading">'.$esc($lang('mod_rubric_actions')).'</th>'
        .'</tr></thead><tbody>';
    foreach ($records as $record) {
        $id = $record['id'];
        $viewUrl = $url(array('action'=>'viewtable', 'tableId'=>$id));
        $actions = $button($viewUrl, $lang('word_view', 'system', 'View'), 'eye');
        $canModify = !isset($record['canModify']) || !empty($record['canModify']);
        if ($canModify && $this->isValid('edittable')) {
            $actions .= $button($url(array('action'=>'edittable', 'tableId'=>$id)), $lang('word_edit', 'system', 'Edit'), 'pencil');
        }
        if ($canModify && $this->isValid('renametable')) {
            $actions .= $button($url(array('action'=>'renametable', 'tableId'=>$id)), $lang('word_rename1', 'system', 'Rename'), 'square-pen');
        }
        if ($this->isValid('clonetable')) {
            $actions .= $button($url(array('action'=>'clonetable', 'tableId'=>$id)), $lang('word_copy', 'system', 'Copy'), 'file-text');
        }
        if ($scope === 'personal' && $contextCode !== 'root' && $this->isValid('copytable')) {
            $actions .= $button($url(array('action'=>'copytable', 'tableId'=>$id)), $lang('mod_rubric_copytocontext'), 'book-open');
        }
        if ($scope === 'course' && $this->isValid('assessments')) {
            $actions .= $button($url(array('action'=>'assessments', 'tableId'=>$id)), $lang('word_assessment', 'system', 'Assessments'), 'clipboard-pen');
        }
        if ($canModify && $this->isValid('deletetable')) {
            $confirm = $lang('mod_rubric_confirmdelete').' '.$record['title'].'?';
            $actions .= $button($url(array('action'=>'deletetable', 'tableId'=>$id)), $lang('word_delete', 'system', 'Delete'), 'trash-2', 'rubric-button-danger', $confirm);
        }
        $out .= '<tr><th scope="row"><a href="'.$viewUrl.'">'.$esc($record['title']).'</a></th>'
            .'<td>'.$esc($record['description']).'</td><td class="chisimba-table-actions"><div class="rubric-row-actions">'.$actions.'</div></td></tr>';
    }
    return $out.'</tbody></table></div></section>';
};

$courseCreate = '';
if ($contextCode !== 'root' && $this->isValid('createtable')) {
    $courseCreate = $button($url(array('action'=>'createtable', 'type'=>'context')), $lang('mod_rubric_create_course'), 'plus', '');
}
$personalCreate = '';
if ($this->isValid('createtable')) {
    $personalCreate = $button($url(array('action'=>'createtable', 'type'=>'predefined')), $lang('mod_rubric_create_personal'), 'plus', '');
}
$sharedCreate = '';
if ($contextCode === 'root' && $this->objUser->isAdmin()) {
    $sharedCreate = $button($url(array('action'=>'createtable', 'type'=>'shared')), $lang('rubric_create_shared'), 'plus', '');
}

echo '<main class="rubric-workspace"><header class="rubric-page-header"><div><h1>'.$esc($lang('rubric_rubrics')).'</h1>'
    .'<p>'.$esc($lang('mod_rubric_library_intro')).'</p></div></header>';
if ($contextCode !== 'root') {
    echo $renderSection($lang('mod_rubric_course_heading'), $lang('mod_rubric_course_description'), (array) $tables, 'course', $courseCreate);
}
echo $renderSection($lang('rubric_shared'), $lang('mod_rubric_shared_description'), (array) $sharedtables, 'shared', $sharedCreate);
if ($contextCode === 'root' || $this->objUser->isContextLecturer($this->objUser->userId(), $contextCode)) {
    echo $renderSection(ucwords($objLanguage->code2Txt('rubric_predefined', 'rubric')), $lang('mod_rubric_personal_description'), isset($pdtables) ? (array) $pdtables : array(), 'personal', $personalCreate);
}
echo '</main>';
?>
