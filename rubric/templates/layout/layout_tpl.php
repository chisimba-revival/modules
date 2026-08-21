Failed to create stream fd: Operation not permitted
Failed to create stream fd: Operation not permitted
Failed to create stream fd: Operation not permitted
<?php
$this->appendArrayVar('headerParams', '<style type="text/css">'
    .'.rubric-scope-bar{display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;margin:0 0 1rem;padding:.75rem 1rem;border:1px solid #cfd8dc;border-radius:.5rem;background:#f5f8f8;color:#37474f}'
    .'.rubric-scope-bar strong{color:#263238}'
    .'.rubric-matrix-scroll{box-sizing:border-box;width:100%;max-width:100%;overflow-x:auto;padding-bottom:.5rem}'
    .'.rubric-matrix-scroll table{width:max-content!important;min-width:100%;border-collapse:separate}'
    .'.rubric-matrix-scroll th,.rubric-matrix-scroll td{min-width:14rem;vertical-align:top}'
    .'.rubric-matrix-scroll th:first-child,.rubric-matrix-scroll td:first-child{min-width:12rem}'
    .'.rubric-matrix-scroll input[type=text],.rubric-matrix-scroll textarea{box-sizing:border-box;width:100%;min-width:12rem}'
    .'.rubric-notice{margin:0 0 1rem;padding:.75rem 1rem;border-radius:.4rem;background:#eaf7ed;color:#185c2b}'
    .'.rubric-notice.error{border:1px solid #d99;background:#fff0f0;color:#8a1f1f}'
	.'.rubric_main{box-sizing:border-box;width:min(100%,92rem);margin-inline:auto}'
	.'.rubric_main--wide{width:calc(100% - 2 * clamp(.75rem,1.5vw,1.5rem));max-width:120rem}'
    .'@media(max-width:48rem){.rubric-scope-bar{display:block}.rubric-scope-bar span{display:block;margin:.2rem 0}.rubric-matrix-scroll th,.rubric-matrix-scroll td{min-width:12rem}}'
    .'</style>');
$objDBContext = & $this->getObject('dbcontext','context');
if($objDBContext->isInContext())
{
    $objContextUtils = & $this->getObject('utilities','context');
    $cm = $objContextUtils->getHiddenContextMenu('rubric','show');
} else {
    $cm = '';//$this->getMenu();
}

$cssLayout =& $this->newObject('csslayout', 'htmlelements');
/*if ($this->objUser->isContextLecturer()|| $this->objUser->isContextStudent() ) {
	$userMenuBar=& $this->getObject('sidemenu','toolbar');
}
else if ($this->objUser->isLecturer()) {
	$userMenuBar=& $this->getObject('sidemenu','toolbar');
}
else {
	die('Access denied');
}*/
$userMenuBar=& $this->getObject('sidemenu','toolbar');
$toolbar = $this->getObject('contextsidebar', 'context');
if (empty($rubricFullWidth)) {
    $cssLayout->setLeftColumnContent($toolbar->show());
} else {
	$cssLayout->setNumColumns(1);
}
$ret = $this->getContent();
$scopeBar = '';
if (isset($rubricScope) && $rubricScope !== '') {
    $scopeBar = '<div class="rubric-scope-bar"><span><strong>'
        .$this->objLanguage->languageText('rubric_resource_scope', 'rubric')
        .':</strong> '.htmlspecialchars($rubricScope, ENT_QUOTES, 'UTF-8').'</span></div>';
}
$ret = $scopeBar.$ret;
$rubricMainClass = empty($rubricFullWidth) ? 'rubric_main' : 'rubric_main rubric_main--wide';
$ret = '<div class="'.$rubricMainClass.'">'.$ret.'</div>';
$cssLayout->setMiddleColumnContent($ret);
echo $cssLayout->show();
?>
