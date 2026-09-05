<?php
$e=function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$wash=$this->getObject('washout','utilities');
$title=(string)($introOnlyChapter['chaptertitle']??'');
$introduction=(string)($introOnlyChapter['introduction']??'');
?>
<section class="chisimba-form-card chisimba-form-card--wide contextcontent-introduction-only" aria-labelledby="chapter-introduction-title">
<p class="contextcontent-form-eyebrow"><?php echo $e(ucfirst($this->objLanguage->code2Txt('mod_contextcontent_chapterlabel','contextcontent',NULL,'[-chapter-]'))); ?></p>
<h1 id="chapter-introduction-title"><?php echo $e($title); ?></h1>
<div class="contextcontent-chapter-introduction"><?php echo $wash->parseText($introduction); ?></div>
<div class="chisimba-state-notice"><strong><?php echo $e($this->objLanguage->code2Txt('mod_contextcontent_introductiononlyheading','contextcontent',NULL,'Only this [-chapter-] introduction is available.')); ?></strong><p><?php echo $e($this->objLanguage->code2Txt('mod_contextcontent_introductiononlyhelp','contextcontent',NULL,'The learning items in this [-chapter-] are not currently available.')); ?></p></div>
<div class="chisimba-form-actions"><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'showcontextchapters'))); ?>"><?php echo $e($this->objLanguage->code2Txt('mod_contextcontent_backtocontent','contextcontent',NULL,'Back to [-context-] content')); ?></a></div>
</section>
<style>.contextcontent-introduction-only{margin:1.5rem auto}.contextcontent-form-eyebrow{color:var(--chisimba-primary);font-size:.78rem;font-weight:750;letter-spacing:.06em;text-transform:uppercase}.chisimba-state-notice{margin-top:1.25rem;padding:1rem;border-left:4px solid var(--chisimba-warning,#b7791f);background:var(--chisimba-surface-muted,#f5f8fb);border-radius:.4rem}.chisimba-state-notice p{margin:.35rem 0 0}</style>
