<?php
$e=function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
echo '<div class="chisimba-notice chisimba-notice-info"><h1>'.$e($this->objLanguage->code2Txt('mod_contextcontent_sectionnotavailable','contextcontent',NULL,'This [-section-] is not available yet')).'</h1><p>'.$e($this->objLanguage->code2Txt('mod_contextcontent_sectionlocked','contextcontent',NULL,'Complete the previous [-section-] to continue.')).'</p><a class="chisimba-button chisimba-button-primary" href="'.$e($this->uri(array('action'=>'showcontextchapters'))).'">'.$e($this->objLanguage->languageText('mod_contextcontent_viewcontent','contextcontent','View content')).'</a></div>';
?>
