<?php
$language = $this->getObject('language', 'language');
$editingTools = '';
if ($mayEditBlocks) {
    $icons = $this->newObject('geticon', 'htmlelements');
    $icons->setIcon('up'); $upIcon = $icons->show();
    $icons->setIcon('down'); $downIcon = $icons->show();
    $icons->setIcon('delete'); $deleteIcon = $icons->show();
    $switch = $this->getObject('buildcanvas', 'canvas')->getSwitchButton(
        $language->languageText('mod_context_turneditingon', 'context', 'Turn Editing On')
    );
    $options = '<option value="">'
        . htmlspecialchars($language->languageText(
            'phrase_selectone', 'context', 'Select one'
        ), ENT_QUOTES, 'UTF-8') . '...</option>';
    foreach ($availableSidebarBlocks as $block) {
        $options .= '<option value="'
            . htmlspecialchars($block['value'], ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $editingTools = '<div id="editmode" class="mylearning-editmode">' . $switch
        . '</div><div id="leftaddblock" class="mylearning-addblock">'
        . '<h3>' . htmlspecialchars($language->languageText(
            'mod_context_addablock', 'context', 'Add a Block'
        ), ENT_QUOTES, 'UTF-8') . '</h3>'
        . '<label for="ddleftblocks" class="visually-hidden">'
        . htmlspecialchars($language->languageText(
            'mod_mylearning_sidebarblock', 'mylearning', 'Sidebar block'
        ), ENT_QUOTES, 'UTF-8') . '</label>'
        . '<select id="ddleftblocks" name="leftblocks">' . $options . '</select>'
        . '<div id="leftpreview"><div id="leftpreviewcontent"></div>'
        . '<button type="button" id="leftbutton" class="button">'
        . htmlspecialchars($language->languageText(
            'mod_prelogin_addblock', 'system', 'Add Block'
        ), ENT_QUOTES, 'UTF-8') . '</button></div></div>';
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
    theModule = 'mylearning';
    </script>
    <?php echo $this->getJavaScriptFile('contextblocks.js', 'context');
}
$layout = $this->newObject('csslayout', 'htmlelements');
$layout->setNumColumns(2);
$accountMenu = $this->getObject('postloginmenu', 'toolbar')->show();
$layout->setLeftColumnContent(
    '<aside class="mylearning-sidebar" aria-label="Student navigation">'
    . $accountMenu
    . $editingTools
    . '<div id="leftblocks" class="mylearning-sidebar__blocks">'
    . $sidebarBlocks . '</div>'
    . '</aside>'
);
$layout->setMiddleColumnContent(
    '<main class="mylearning-page">' . $learningOverview . '</main>'
);
echo $layout->show();
?>
