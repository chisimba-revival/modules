<?php $e=function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}; ?>
<section class="chisimba-form-card contextcontent-unavailable" aria-labelledby="chapter-unavailable-title">
<h1 id="chapter-unavailable-title"><?php echo $e($this->objLanguage->code2Txt('mod_contextcontent_chapterunavailableheading','contextcontent',NULL,'This [-chapter-] is not available')); ?></h1>
<p><?php echo $e($this->objLanguage->code2Txt('mod_contextcontent_chapterunavailablehelp','contextcontent',NULL,'Return to the [-context-] content to continue.')); ?></p>
<div class="chisimba-form-actions"><a class="button" href="<?php echo $e($this->uri(array('action'=>'showcontextchapters'))); ?>"><?php echo $e($this->objLanguage->code2Txt('mod_contextcontent_backtocontent','contextcontent',NULL,'Back to [-context-] content')); ?></a></div>
</section>
<style>.contextcontent-unavailable{margin:1.5rem auto}</style>
