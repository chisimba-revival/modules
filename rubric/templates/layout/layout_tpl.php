<?php
$this->appendArrayVar('headerParams', '<style type="text/css">'
	.'.rubric-workspace{box-sizing:border-box;width:100%;max-width:92rem;margin-inline:auto}'
	.'.rubric_main .button{appearance:none;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;min-height:2.4rem;padding:.46rem .82rem;border:1px solid var(--chisimba-primary);border-radius:var(--chisimba-radius-sm,.35rem);background:var(--chisimba-primary);color:var(--chisimba-text-inverse);font:inherit;font-weight:700;line-height:1.2;text-decoration:none;cursor:pointer;box-shadow:none}'
	.'.rubric_main .button:hover,.rubric_main .button:focus-visible{border-color:var(--chisimba-primary-dark);background:var(--chisimba-primary-dark);color:var(--chisimba-text-inverse)}'
	.'.rubric_main .chisimba-button-secondary{border-color:var(--chisimba-border);background:var(--chisimba-surface-muted);color:var(--chisimba-ink)}'
	.'.rubric_main .chisimba-button-secondary:hover,.rubric_main .chisimba-button-secondary:focus-visible{border-color:var(--chisimba-border);background:var(--chisimba-primary-soft);color:var(--chisimba-ink)}'
	.'.rubric_main .chisimba-action-icon{width:1.05rem;height:1.05rem;flex:0 0 auto}'
	.'.rubric-page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin:0 0 1.5rem}'
	.'.rubric-page-header h1,.rubric-section-heading h2{margin:0 0 .35rem;color:var(--chisimba-ink)}'
	.'.rubric-page-header p,.rubric-section-heading p{max-width:60rem;margin:0;color:var(--chisimba-text-muted)}'
	.'.rubric-library-section{margin:0 0 1.5rem;border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-surface);overflow:hidden}'
	.'.rubric-section-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.1rem;border-bottom:1px solid var(--chisimba-border);background:var(--chisimba-surface-muted)}'
	.'.rubric-section-heading .button{flex:0 0 auto}'
	.'.rubric-library-table{border:0}.rubric-library-table th:first-child{width:24%}.rubric-library-table td:nth-child(2){width:40%}'
	.'.rubric-actions-heading{text-align:right!important}.rubric-row-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.4rem}'
	.'.rubric-row-actions .button{min-height:2.15rem;padding:.36rem .58rem;font-size:.9rem}'
	.'.rubric-empty{padding:1.25rem}.rubric-empty h3{margin:0 0 .3rem}.rubric-empty p{margin:0;color:var(--chisimba-text-muted)}'
	.'.rubric_main .rubric-button-danger{display:inline-flex;align-items:center;gap:.35rem;border:1px solid var(--chisimba-danger,#b42318);background:transparent;color:var(--chisimba-danger,#b42318)}'
	.'.rubric-button-danger:hover,.rubric-button-danger:focus-visible{background:var(--chisimba-danger,#b42318);color:var(--chisimba-text-inverse)}'
	.'.rubric-editor-header{margin:0 0 1rem}.rubric-editor-header h1{margin:0 0 .35rem}.rubric-editor-header p{margin:.25rem 0;color:var(--chisimba-text-muted)}'
	.'.rubric-editor-help{margin:0 0 1rem;padding:.85rem 1rem;border-left:.25rem solid var(--chisimba-primary);background:var(--chisimba-primary-soft)}'
	.'.rubric-editor-help summary{cursor:pointer;font-weight:700}.rubric-editor-help ol{margin:.75rem 0 0;padding-left:1.4rem}'
	.'.rubric-matrix{border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-surface);overflow:hidden}'
	.'.rubric-matrix table{margin:0}.rubric-matrix th{background:var(--chisimba-surface-muted)}'
	.'.rubric-matrix textarea{min-height:7rem;resize:vertical}.rubric-matrix th textarea{min-height:3.25rem}'
	.'.rubric-matrix label{display:block;margin:0 0 .3rem;font-size:.85rem;font-weight:700;color:var(--chisimba-text-muted)}'
	.'.rubric-editor-actions,.rubric-form-actions,.rubric-view-actions{display:flex;align-items:center;flex-wrap:wrap;gap:.6rem;margin:1rem 0}'
	.'.rubric-structure-actions{display:flex;flex-wrap:wrap;gap:.5rem;margin:1rem 0;padding:1rem;border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-surface-muted)}'
	.'.rubric-form{max-width:52rem}.rubric-form-card{padding:1.1rem;border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-surface)}'
	.'.rubric-form-field{margin:0 0 1rem}.rubric-form-field label{display:block;margin:0 0 .35rem;font-weight:700}.rubric-form-field input,.rubric-form-field select,.rubric-form-field textarea{box-sizing:border-box;width:100%;max-width:100%}'
	.'.rubric-form-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin:0 0 1rem}.rubric-form-meta div{padding:.75rem;border-radius:.35rem;background:var(--chisimba-surface-muted)}.rubric-form-meta strong{display:block;margin-bottom:.2rem}'
	.'.rubric-view-header h1{margin:0 0 .35rem}.rubric-view-header p{margin:0 0 1rem;color:var(--chisimba-text-muted)}'
	.'.rubric-visually-hidden{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}'
    .'.rubric-scope-bar{display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;margin:0 0 1rem;padding:.75rem 1rem;border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-surface-muted);color:var(--chisimba-ink)}'
    .'.rubric-scope-bar strong{color:var(--chisimba-ink)}'
    .'.rubric-matrix-scroll{box-sizing:border-box;width:100%;max-width:100%;overflow-x:auto;padding-bottom:.5rem}'
    .'.rubric-matrix-scroll table{width:max-content!important;min-width:100%;border-collapse:separate}'
    .'.rubric-matrix-scroll th,.rubric-matrix-scroll td{min-width:14rem;vertical-align:top}'
    .'.rubric-matrix-scroll th:first-child,.rubric-matrix-scroll td:first-child{min-width:12rem}'
    .'.rubric-matrix-scroll input[type=text],.rubric-matrix-scroll textarea{box-sizing:border-box;width:100%;min-width:12rem}'
    .'.rubric-notice{margin:0 0 1rem;padding:.75rem 1rem;border:1px solid var(--chisimba-border);border-radius:var(--chisimba-radius-md,.5rem);background:var(--chisimba-primary-soft);color:var(--chisimba-ink)}'
    .'.rubric-notice.error{border-color:var(--chisimba-danger,#b42318);background:var(--chisimba-surface);color:var(--chisimba-danger,#b42318)}'
	.'.rubric_main{box-sizing:border-box;width:min(100%,92rem);margin-inline:auto}'
	.'.rubric_main--wide{width:calc(100% - 2 * clamp(.75rem,1.5vw,1.5rem));max-width:120rem}'
	.'#Canvas_Content_Body:has(.rubric_main--wide){display:block!important;grid-template-columns:minmax(0,1fr)!important}'
	.'#Canvas_Content_Body:has(.rubric_main--wide)>#onecolumn{display:block!important;float:none!important;margin-inline:auto!important;width:100%!important;max-width:none!important}'
	.'#Canvas_Content_Body:has(.rubric_main--wide)>#onecolumn>#content,#Canvas_Content_Body:has(.rubric_main--wide)>#onecolumn>#content>#contentcontent{box-sizing:border-box;float:none!important;margin:0!important;width:100%!important;max-width:none!important}'
    .'@media(max-width:48rem){.rubric-section-heading,.rubric-page-header{align-items:flex-start;flex-direction:column}.rubric-form-meta{grid-template-columns:1fr}.rubric-scope-bar{display:block}.rubric-scope-bar span{display:block;margin:.2rem 0}.rubric-matrix-scroll th,.rubric-matrix-scroll td{min-width:12rem}}'
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
