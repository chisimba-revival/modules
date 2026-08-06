<?php
$showNav = TRUE;
$objModule = $this->getObject('modules', 'modulecatalogue');
if (isset($hideNavSwitch) && $hideNavSwitch) {
    $showNav = FALSE;
}



$this->loadClass('link', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('label', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');



if (isset($currentChapter)) {



    if (!isset($currentChapterTitle)) {
        $currentChapterTitle = $this->objContextChapters->getContextChapterTitle($currentChapter);
    }

    if (!isset($currentPage)) {
        $currentPage = '';
    }

    $heading = new htmlheading();

    $heading->str = $currentChapterTitle;
    $heading->type = 3;
    /*
    $form = new form ('searchform', $this->uri(array('action'=>'search')));
    $form->method = 'GET';

    $hiddenInput = new hiddeninput('module', 'contextcontent');
    $form->addToForm($hiddenInput->show());

    $hiddenInput = new hiddeninput('action', 'search');
    $form->addToForm($hiddenInput->show());

    $textinput = new textinput ('contentsearch');
    $button = new button ('searchgo', 'Go');
    $button->setToSubmit();

    $form->addToForm($textinput->show().' '.$button->show());

    $objFieldset = $this->newObject('fieldset', 'htmlelements');
    $label = new label ($this->objLanguage->languageText('mod_forum_searchfor', 'forum', 'Search for').':', 'input_contentsearch');

    $objFieldset->setLegend($label->show());
    $objFieldset->contents = $form->show();
    */
    $header = new htmlHeading();
    $header->str = ucwords($this->objLanguage->code2Txt('mod_contextcontent_name', 'contextcontent', NULL, '[-context-] Content'));
    $header->type = 2;

    $left = $header->show();

    $pageId = isset($currentPage) ? $currentPage : '';
    $id = isset($currentChapter) ? $currentChapter : '';
    $left .= $heading->show();

    // CHISIMBA_CONTEXTCONTENT_TREE_ONLY: the unambiguous tree is the
    // only current course-content navigation. Legacy renderers and data remain
    // available for a later content-model review, but are not exposed here.
    $left .= '<div id="contentnav">';
    $left .= $this->objContentOrder->getTree(
        $this->contextCode,
        $currentChapter,
        'htmllist',
        $pageId,
        'contextcontent'
    );
    $left .= '</div>';

    if ($this->isValid('addpage')) {
        $addLink = new link ($this->uri(array('action'=>'addpage', 'chapter'=>$currentChapter, 'id'=>$currentPage)));
        $addLink->link = 'Add a Page';

        $addPageFromFileLink = new link($this->uri(array('action'=>'addpagefromfile', 'chapterid'=>$currentChapter)));
        $addPageFromFileLink->link = $this->objLanguage->languageText('mod_contextcontent_createpagefromfile', 'contextcontent', 'Create page from file');
        $scormInstalled = $objModule->checkIfRegistered("scorm");
        if ($scormInstalled) {
            $addScormLink = new link ($this->uri(array('action'=>'addscormpage', 'id'=>$id, 'context'=>$this->contextCode, 'chapter'=>$currentChapter)));
            $addScormLink->link = $this->objLanguage->languageText('mod_contextcontent_addcontextscormpages','contextcontent');
            $scormLink = $addScormLink->show();
        } else {
            $scormLink = NULL;
        }
        $left .= '<hr /><p>'.$addLink->show().'&nbsp;&nbsp;'.$scormLink.'</p>';
    }

    $returnLink = new link ($this->uri(NULL));
    $returnLink->link = $this->objLanguage->languageText('mod_contextcontent_returntochapterlist', 'contextcontent', 'Return to Chapter List');

    $left .= '<hr /><p>'.$returnLink->show().'</p>';
    $left = '<div id="context_left_nav">' . $left . '</div>';

    //Add toolbar
    $toolbar = $this->getObject('contextsidebar', 'context');
    $objFieldset = $this->newObject('fieldset', 'htmlelements');
    $objFieldset->contents = $toolbar->show();
//$objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
    $cols = $this->objSysConfig->getValue('CONTEXTCONTENT_COLUMNS', 'contextcontent');
    $cols = (integer)$cols;
    $cssLayout = $this->newObject('csslayout', 'htmlelements');
    $cssLayout->setNumColumns($cols);
    switch ($cols) {
        case 1:

            break;
        case 2:
            $cssLayout->setLeftColumnContent($left . $objFieldset->show());
            break;
        case 3:
        default:
            $cssLayout->setLeftColumnContent($left);
            $cssLayout->setRightColumnContent($objFieldset->show());
            break;
    }
    $cssLayout->setMiddleColumnContent($this->getContent());
    echo $cssLayout->show();

} else {
    echo $this->getContent();
}
?>
