<?php
$language = $this->getObject('language', 'language');
$managementNotice = !empty($managingDashboard)
    ? '<section class="chisimba-notice chisimba-notice--info"><strong>Managing the My Teaching page</strong><p>' . htmlspecialchars($language->code2Txt('mod_myteaching_managehelp','myteaching',null,'Changes to dashboard blocks affect the shared teaching experience. [-context-] cards below remain limited to [-contexts-] where this account is part of the teaching team.'),ENT_QUOTES,'UTF-8') . '</p></section>'
    : '';
$editingSwitch = '';
$upperEditor = '';
$lowerEditor = '';
$wideEditor = '';
if ($mayEditBlocks) {
    $switch = '<button type="button" id="editmodeswitchbutton" aria-pressed="false" onclick="switchEditMode()">'
        . htmlspecialchars($language->languageText('mod_context_turneditingon', 'context'), ENT_QUOTES, 'UTF-8') . '</button>';
    $switch .= $this->getObject('contextualhelp', 'help')->show('context', 'dashboard-editing');
    $makeOptions = static function ($blocks) use ($language) {
        $html = '<option value="">' . htmlspecialchars(
            $language->languageText('phrase_selectone', 'context', 'Select one'),
            ENT_QUOTES, 'UTF-8'
        ) . '...</option>';
        foreach ($blocks as $block) {
            $html .= '<option value="'
                . htmlspecialchars($block['value'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8')
                . '</option>';
        }
        return $html;
    };
    $makeEditor = static function ($side, $label, $options) use ($language) {
        return '<div id="' . $side . 'addblock" class="myteaching-addblock">'
            . '<h3>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h3>'
            . '<select id="dd' . $side . 'blocks" name="' . $side
            . 'blocks" aria-label="'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
            . $options . '</select>'
            . '<div id="' . $side . 'preview"><div id="' . $side
            . 'previewcontent"></div><button type="button" id="' . $side
            . 'button" class="button">' . htmlspecialchars(
                $language->languageText(
                    'mod_prelogin_addblock', 'system', 'Add Block'
                ), ENT_QUOTES, 'UTF-8'
            ) . '</button></div></div>';
    };
    $narrowOptions = $makeOptions($availableNarrowBlocks);
    $wideOptions = $makeOptions($availableWideBlocks);
    $editingSwitch = '<div id="editmode" class="myteaching-editmode chisimba-form-actions chisimba-form-actions--equal">'
        . $switch . '</div>';
    $upperEditor = $makeEditor('right', $language->languageText(
        'mod_myteaching_addupperblock', 'myteaching', 'Add an upper block'
    ), $narrowOptions);
    $lowerEditor = $makeEditor('left', $language->languageText(
        'mod_myteaching_addlowerblock', 'myteaching', 'Add a lower block'
    ), $narrowOptions);
    $wideEditor = $makeEditor('middle', $language->languageText(
        'mod_myteaching_addwideblock', 'myteaching', 'Add a wide block'
    ), $wideOptions);
    echo $this->getObject('buildcanvas', 'canvas')->getScriptValues();
    echo $blockEditor->settings($blockEditingRevision);
    echo $this->getJavaScriptFile('contextblocks.js?v=2060', 'context');
}
$layout = $this->newObject('csslayout', 'htmlelements');
$layout->setNumColumns(2);
$accountMenu = $this->getObject('postloginmenu', 'toolbar')->show();
$layout->setLeftColumnContent(
    '<aside class="chisimba-structural-sidebar myteaching-sidebar" aria-label="Teaching navigation">'
    . $accountMenu
    . '<div id="rightblocks" class="myteaching-sidebar__blocks myteaching-sidebar__blocks--upper">'
    . $upperBlocks . '</div>'
    . $upperEditor
    . $editingSwitch
    . '<div id="leftblocks" class="myteaching-sidebar__blocks">'
    . $lowerBlocks . '</div>'
    . $lowerEditor
    . '</aside>'
);
$layout->setMiddleColumnContent(
    '<main class="myteaching-page">' . $managementNotice . $teachingOverview
    . '<div id="middleblocks" class="myteaching-page__blocks">'
    . $wideBlocks . '</div>' . $wideEditor . '</main>'
);
echo $layout->show();
?>
