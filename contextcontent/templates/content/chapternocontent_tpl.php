<?php
$ret ="";
$this->loadClass('link', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');
/* CHISIMBA NATIVE EMPTY-CHAPTER ACTIONS */
$objIconService = $this->getObject('iconservice', 'ui');
$editLabel = $this->objLanguage->languageText(
    'mod_contextcontent_editchapter',
    'contextcontent',
    'Edit Chapter'
);
$deleteLabel = $this->objLanguage->languageText(
    'mod_contextcontent_deletechapter',
    'contextcontent',
    'Delete Chapter'
);
$addPageLabel = $this->objLanguage->languageText(
    'mod_contextcontent_addapagetothischapter',
    'contextcontent',
    'Add a Page to this Chapter'
);
$editIcon = $objIconService->render('pencil', array('decorative' => TRUE));
$deleteIcon = $objIconService->render('trash-2', array('decorative' => TRUE));
$addPageIcon = $objIconService->render('plus', array('decorative' => TRUE));
$editLink = new link($this->uri(array('action'=>'editchapter', 'id'=>$chapter['chapterid'])));
$editLink->link = $editIcon;
$editLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-edit';
$editLink->extra = ' aria-label="' . htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8')
    . '" title="' . htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') . '"';
$deleteLink = new link($this->uri(array('action'=>'deletechapter', 'id'=>$chapter['chapterid'])));
$deleteLink->link = $deleteIcon;
$deleteLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-delete';
$deleteLink->extra = ' aria-label="' . htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8')
    . '" title="' . htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') . '"';
$addPageLink = new link($this->uri(array('action'=>'addpage', 'chapter'=>$chapter['chapterid'])));
$addPageLink->link = $addPageIcon;
$addPageLink->cssClass = 'chisimba-chapter-action chisimba-chapter-action-add';
$addPageLink->extra = ' aria-label="' . htmlspecialchars($addPageLabel, ENT_QUOTES, 'UTF-8')
    . '" title="' . htmlspecialchars($addPageLabel, ENT_QUOTES, 'UTF-8') . '"';
$chapters = $this->objContextChapters->getContextChapters($this->contextCode);
$this->setVarByRef('chapters', $chapters);
$this->setLayoutTemplate('layout_firstpage_tpl.php');
$chapterlink=new htmlheading();
$chapterlink->type=1;
$con=$chapter['chaptertitle'];
if ($this->isValid('editchapter')) {
    $con.= ' '.$editLink->show();
}

if ($this->isValid('deletechapter')) {
    $con.= ' '.$deleteLink->show();
}

if ($this->isValid('addpage')) {
    $con.= ' '.$addPageLink->show();//.' / '.$addPageFromFileLink->show();
}
$chapterlink->str=$con;
$ret .= $chapterlink->show();

if ($this->getParam('message') == 'chaptercreated') {
    $ret .= '<p class="warning">'.$errorTitle.'</p>';
} else {
    $ret .= '<p class="error">'.$errorTitle.'. '.$errorMessage.'</p>';
}

/** removed at request of Eteaching customer
$introheader=new htmlheading();
$introheader->type=3;
$introheader->str=$this->objLanguage->languageText('mod_contextcontent_aboutchapter_introduction', 'contextcontent', 'About Chapter (Introduction)');
echo $introheader->show();
**/

$objWashout = $this->getObject('washout', 'utilities');

$ret .= $objWashout->parseText($chapter['introduction']);

$addPageLink = new link ($this->uri(array('action'=>'addpage', 'chapter'=>$chapter['chapterid'])));
$addPageLink->link = $this->objLanguage->languageText('mod_contextcontent_addapagetothischapter','contextcontent');

$addPageFromFileLink = new link ($this->uri(array('action'=>'addpagefromfile', 'context'=>$this->contextCode, 'chapterid'=>$chapter['chapterid'])));
$addPageFromFileLink->link = $this->objLanguage->languageText('mod_contextcontent_createpagefromfile','contextcontent','Create page from file');


if ($this->isValid('addpage')) {
     $ret .= $addPageLink->show().' / ';//.' / '.$addPageFromFileLink->show().' / ';
}

$returnLink = new link ($this->uri(NULL));
$returnLink->link = $this->objLanguage->languageText('mod_contextcontent_returntochapterlist', 'contextcontent', 'Return to Chapter List');

$ret .= $returnLink->show();

$ret = '<div id="context_content">' . $ret . '</div>';
echo $ret;

?>
