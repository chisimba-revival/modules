<?php
$objLanguage=$this->getObject('language','language');
$objIconService=$this->getObject('iconservice','ui');
    $iconOptions = array('decorative' => TRUE, 'class' => 'block-control__icon');
    $upIcon = $objIconService->render('chevron-up', $iconOptions);
    $downIcon = $objIconService->render('chevron-down', $iconOptions);
    $deleteIcon = $objIconService->render('trash-2', $iconOptions);
    $moveUpLabel = $objLanguage->languageText(
        'mod_context_moveblockup', 'context', 'Move block up');
    $moveDownLabel = $objLanguage->languageText(
        'mod_context_moveblockdown', 'context', 'Move block down');
    $removeBlockLabel = $objLanguage->languageText(
        'mod_context_removeblock', 'context', 'Remove block');
    $blockControlsLabel = $objLanguage->languageText(
        'mod_context_blockcontrols', 'context', 'Block controls');
    ?>
    <script type="text/javascript">
        // <![CDATA[
        upIcon = <?php echo json_encode($upIcon); ?>;
        downIcon = <?php echo json_encode($downIcon); ?>;
        deleteIcon = <?php echo json_encode($deleteIcon); ?>;
        moveUpLabel = <?php echo json_encode($moveUpLabel); ?>;
        moveDownLabel = <?php echo json_encode($moveDownLabel); ?>;
        removeBlockLabel = <?php echo json_encode($removeBlockLabel); ?>;
        blockControlsLabel = <?php echo json_encode($blockControlsLabel); ?>;
        deleteConfirm = <?php echo json_encode(html_entity_decode($objLanguage->languageText('mod_context_confirmremoveblock', 'context'), ENT_QUOTES, 'UTF-8')); ?>;
        unableMoveBlock = <?php echo json_encode(html_entity_decode($objLanguage->languageText('mod_context_unablemoveblock', 'context'), ENT_QUOTES, 'UTF-8')); ?>;
        unableDeleteBlock = <?php echo json_encode(html_entity_decode($objLanguage->languageText('mod_context_unabledeleteblock', 'context'), ENT_QUOTES, 'UTF-8')); ?>;
        unableAddBlock = <?php echo json_encode(html_entity_decode($objLanguage->languageText('mod_context_unableaddblock', 'context'), ENT_QUOTES, 'UTF-8')); ?>;
        turnEditingOn = <?php echo json_encode(html_entity_decode($objLanguage->languageText('mod_context_turneditingon', 'context'), ENT_QUOTES, 'UTF-8')); ?>;
        turnEditingOff = <?php echo json_encode(html_entity_decode($objLanguage->languageText('mod_context_turneditingoff', 'context'), ENT_QUOTES, 'UTF-8')); ?>;
        theModule = 'personalspace';

        // ]]>
    </script>


    <?php
    $editor=$this->getObject('personalspaceeditor','personalspace');
    $attributes=['module'=>'personalspace','owner'=>$this->objUser->userId(),'csrf'=>$editor->csrf()->issue($editor->purpose($this->objUser->userId())),
        'revision'=>$blockEditingRevision,'cancel'=>html_entity_decode($objLanguage->languageText('word_cancel'),ENT_QUOTES,'UTF-8')];
    foreach(['saving','saved','failed','stale','uncertain','denied','method','csrf-error'] as $key) {
        $attributes[$key]=html_entity_decode($objLanguage->languageText('mod_context_block_'.($key==='csrf-error'?'csrf':$key),'context'),ENT_QUOTES,'UTF-8');
    }
    $editorStatus = '<div id="block-editor-settings"';
    foreach($attributes as $key=>$value)$editorStatus .= ' data-'.$key.'="'.htmlspecialchars($value,ENT_QUOTES,'UTF-8').'"';
    $editorStatus .= '><p id="block-editor-status" role="status" aria-live="polite"></p></div>';
    echo $this->getJavaScriptFile('contextblocks.js?v=2057', 'context');
$editorHelp=$this->getObject('contextualhelp','help')->show('personalspace','editing');

$this->loadClass('dropdown', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');

$objCssLayout = $this->getObject('csslayout', 'htmlelements');
$objCssLayout->setNumColumns(3);



$smallBlocksDropDown = new dropdown('rightblocks');
$smallBlocksDropDown->cssId = 'ddrightblocks';
$smallBlocksDropDown->addOption('', $objLanguage->languageText('phrase_selectone', 'context', 'Select One') . '...');

// Create array for sorting
$smallBlockOptions = array();

// Add Small Dynamic Blocks
foreach ($smallDynamicBlocks as $smallBlock) {
    $smallBlockOptions['dynamicblock|' . $smallBlock['id'] . '|' . $smallBlock['module']] = htmlentities($smallBlock['title']);
}

// Add Small Blocks
foreach ($smallBlocks as $smallBlock) {
    if ($smallBlock['moduleid'] != "contentblocks") {
        $block = $this->newObject('block_' . $smallBlock['blockname'], $smallBlock['moduleid']);
        $title = $block->title;

        if ($title == '') {
            $title = $smallBlock['blockname'] . '|' . $smallBlock['moduleid'];
        } else {
            $title = $title . "(" . $smallBlock['moduleid']. ")";
        }

        $smallBlockOptions['block|' . $smallBlock['blockname'] . '|' . $smallBlock['moduleid']] = htmlentities($title);
    } else {

        // fetch contentblock data
        $block = $this->objBlocks->showBlock($smallBlock["id"], "contentblocks");
        $bData = $this->objBlocksContent->getBlockById($smallBlock["id"]);
        if (!empty($bData)) {
            $bData = $bData[0];
            $title = $bData["title"];
            //parse some abstractions
            $title = $objLanguage->abstractText($title);
            if ($title == '') {
                $title = $smallBlock['blockname'] . ' | ';
            }
            $title .= " (" . $smallBlock['moduleid'] . ")";
            $smallBlockOptions['block|' . $bData["id"] . '|' . "contentblocks"] = htmlentities($bData["title"] . ' (contentblocks)');
        }
    }
}

// Sort Alphabetically
asort($smallBlockOptions);

// Add Small Blocks
foreach ($smallBlockOptions as $block => $title) {
    $smallBlocksDropDown->addOption($block, $title);
}

//Add content sideblocks to options
if (!empty($contentSmallBlocks)) {
    foreach ($contentSmallBlocks as $contentSmallBlock) {
        $smallBlocksDropDown->addOption('block|' . $contentSmallBlock["id"] . '|' . "contentblocks", htmlentities($contentSmallBlock["title"]) . '(contentblocks)');
    }
}
$rightBlocks = $smallBlocksDropDown->show();

$smallBlocksDropDown->cssId = 'ddleftblocks';
$smallBlocksDropDown->name = 'leftblocks';

$leftBlocks = $smallBlocksDropDown->show();


// ------------------------------
// Create array for sorting
$wideBlockOptions = array();

$wideBlocksDropDown = new dropdown('middleblocks');
$wideBlocksDropDown->cssId = 'ddmiddleblocks';
$wideBlocksDropDown->addOption('', $objLanguage->languageText('phrase_selectone', 'context', 'Select One') . '...');

foreach ($wideDynamicBlocks as $wideBlock) {
    $wideBlockOptions['dynamicblock|' . $wideBlock['id'] . '|' . $wideBlock['module']] = htmlentities($wideBlock['title']);
}

foreach ($wideBlocks as $wideBlock) {
    if ($wideBlock["moduleid"] != "contentblocks") {
        $block = $this->newObject('block_' . $wideBlock['blockname'], $wideBlock['moduleid']);
        $title = $block->title;

        if ($title == '') {
            $title = $wideBlock['blockname'] . '|' . $wideBlock['moduleid'];
        } else {
            $title = $title . " (" . $wideBlock['moduleid'] . ")";
        }

        $wideBlockOptions['block|' . $wideBlock['blockname'] . '|' . $wideBlock['moduleid']] = htmlentities($title);
    } else {
        // fetch contentblock data
        $block = $this->objBlocks->showBlock($wideBlock["id"], "contentblocks");
        $bData = $this->objBlocksContent->getBlockById($wideBlock["id"]);
        if (!empty($bData)) {
            $bData = $bData[0];
            $title = $bData["title"];
            //parse some abstractions
            $title = $objLanguage->abstractText($title);
            if ($title == '') {
                $title = $wideBlock['blockname'] . ' | ';
            }
            $title .= " (" . $wideBlock['moduleid'] . ")";
            $wideBlockOptions['block|' . $bData["id"] . '|' . "contentblocks"] = htmlentities($bData["title"] . ' (contentblocks)');
        }
    }
}

// Sort Alphabetically
asort($wideBlockOptions);

// Add Small Blocks
foreach ($wideBlockOptions as $block => $title) {
    $wideBlocksDropDown->addOption($block, $title);
}

//Add content wideblocks to options
if (!empty($contentWideBlocks)) {
    foreach ($contentWideBlocks as $contentWideBlock) {
        $wideBlocksDropDown->addOption('block|' . $contentWideBlock["id"] . '|' . "contentblocks", htmlentities($contentWideBlock["title"]) . '(contentblocks)');
    }
}

$button = new button('addrightblock', $objLanguage->languageText('mod_prelogin_addblock', 'system', 'Add Block'));
$button->cssId = 'rightbutton';

$rightButton = $button->show();

$button = new button('addleftblock', $objLanguage->languageText('mod_prelogin_addblock', 'system', 'Add Block'));
$button->cssId = 'leftbutton';

$leftButton = $button->show();

$header = new htmlheading();
$header->type = 3;
$header->str = $objLanguage->languageText('mod_context_addablock', 'context', 'Add a Block');



$userMenu = $this->newObject('usermenu', 'toolbar');
$objCssLayout->setLeftColumnContent($userMenu->show());




$objCssLayout->leftColumnContent .= '<div id="leftblocks">' . $leftBlocksStr . '</div>';


$objCssLayout->leftColumnContent .= '<div id="leftaddblock" data-block-editor>' . '<label for="ddleftblocks">'.htmlspecialchars($objLanguage->languageText('mod_personalspace_leftpicker','personalspace'), ENT_QUOTES, 'UTF-8').'</label>' . $leftBlocks;
$objCssLayout->leftColumnContent .= '<div id="lefttpreview"><div id="leftpreviewcontent"></div> ' . $leftButton . ' </div>';
$objCssLayout->leftColumnContent .= '</div>';


$objCssLayout->rightColumnContent = '';


$value = $objLanguage->languageText('mod_context_turneditingon', 'context', 'Turn Editing On');
$editorToolbar='<div class="chisimba-form-actions"><button type="button" id="editmodeswitchbutton" aria-pressed="false" onclick="switchEditMode()">'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'</button>'.$editorHelp.'</div>';

$objCssLayout->rightColumnContent .= '<div id="rightblocks">' . $rightBlocksStr . '</div>';


$objCssLayout->rightColumnContent .= '<div id="rightaddblock" data-block-editor>' . '<label for="ddrightblocks">'.htmlspecialchars($objLanguage->languageText('mod_personalspace_rightpicker','personalspace'), ENT_QUOTES, 'UTF-8').'</label>' . $rightBlocks;
$objCssLayout->rightColumnContent .= '<div id="rightpreview"><div id="rightpreviewcontent"></div> ' . $rightButton . ' </div>';
$objCssLayout->rightColumnContent .= '</div>';


$button = new button('addmiddleblock', $objLanguage->languageText('mod_prelogin_addblock', 'system', 'Add Block'));
$button->cssId = 'middlebutton';

$objCssLayout->middleColumnContent = $editorToolbar . $editorStatus . '<div id="middleblocks">' . $middleBlocksStr . '</div>';


$objCssLayout->middleColumnContent .= '<div id="middleaddblock" data-block-editor>' . '<label for="ddmiddleblocks">'.htmlspecialchars($objLanguage->languageText('mod_personalspace_middlepicker','personalspace'), ENT_QUOTES, 'UTF-8').'</label>' . $wideBlocksDropDown->show();
$objCssLayout->middleColumnContent .= '<div id="middlepreview"><div id="middlepreviewcontent"></div> ' . $button->show() . ' </div>';
$objCssLayout->middleColumnContent .= '</div>';


echo $objCssLayout->show();