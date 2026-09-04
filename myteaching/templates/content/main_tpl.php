<?php
$language = $this->getObject('language', 'language');
$managementNotice = !empty($managingDashboard)
    ? '<section class="chisimba-notice chisimba-notice--info"><strong>Managing the My Teaching page</strong><p>Changes to dashboard blocks affect the shared teaching experience. Course cards below remain limited to courses where this account is part of the teaching team.</p></section>'
    : '';
$editingSwitch = '';
$upperEditor = '';
$lowerEditor = '';
$wideEditor = '';
if ($mayEditBlocks) {
    $icons = $this->newObject('geticon', 'htmlelements');
    $icons->setIcon('up'); $upIcon = $icons->show();
    $icons->setIcon('down'); $downIcon = $icons->show();
    $icons->setIcon('delete'); $deleteIcon = $icons->show();
    $switch = $this->getObject('buildcanvas', 'canvas')->getSwitchButton(
        $language->languageText('mod_context_turneditingon', 'context', 'Turn Editing On')
    );
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
    $editingSwitch = '<div id="editmode" class="myteaching-editmode">'
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
    ?>
    <script>
    upIcon = <?php echo json_encode($upIcon); ?>;
    downIcon = <?php echo json_encode($downIcon); ?>;
    deleteIcon = <?php echo json_encode($deleteIcon); ?>;
    deleteConfirm = <?php echo json_encode($language->languageText('mod_context_confirmremoveblock', 'context', 'Are you sure you want to remove the block?')); ?>;
    unableMoveBlock = <?php echo json_encode($language->languageText('mod_context_unablemoveblock', 'context', 'Unable to move block.')); ?>;
    unableDeleteBlock = <?php echo json_encode($language->languageText('mod_context_unabledeleteblock', 'context', 'Unable to remove block.')); ?>;
    unableAddBlock = <?php echo json_encode($language->languageText('mod_context_unableaddblock', 'context', 'Unable to add block.')); ?>;
    turnEditingOn = <?php echo json_encode($language->languageText('mod_context_turneditingon', 'context', 'Turn Editing On')); ?>;
    turnEditingOff = <?php echo json_encode($language->languageText('mod_context_turneditingoff', 'context', 'Turn Editing Off')); ?>;
    theModule = 'myteaching';
    </script>
    <?php echo $this->getJavaScriptFile('contextblocks.js', 'context');
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
