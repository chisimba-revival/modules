<?php
$e=static function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$wash=$this->getObject('washout','utilities');
$icons=$this->getObject('iconservice','ui');
$sectionLabel=$this->objLanguage->code2Txt(
    'mod_contextcontent_sectionlabel','contextcontent',NULL,'[-section-]'
);
$continueLabel=$this->objLanguage->code2Txt(
    'mod_contextcontent_continuesection','contextcontent',NULL,
    'Continue to the first [-chapter-]'
);
echo '<article class="contextcontent-section-page"><p class="contextcontent-section-marker">'
    .$icons->render('layers',array('decorative'=>TRUE)).' '.$e(ucfirst($sectionLabel))
    .'</p><h1>'.$e($section['title']).'</h1><div class="contextcontent-section-page__introduction">'
    .$wash->parseText($section['introduction']).'</div>';
if (!empty($section['chapters'])) {
    echo '<h2>'.$e($this->objLanguage->code2Txt(
        'mod_contextcontent_sectionincludes','contextcontent',NULL,
        'This [-section-] includes'
    )).'</h2><ol class="contextcontent-section-page__chapters">';
    foreach ($section['chapters'] as $chapter) {
        echo '<li>'.$e($chapter['chaptertitle']).'</li>';
    }
    echo '</ol>';
}
echo '<div class="chisimba-form-actions">';
if ($firstSectionChapter!==FALSE) {
    echo '<a class="chisimba-button chisimba-button-primary" href="'.$e($this->uri(
        array('action'=>'viewchapter','id'=>$firstSectionChapter['chapterid'])
    )).'">'.$e($continueLabel).'</a>';
}
echo '<a class="chisimba-button chisimba-button-secondary" href="'.$e($this->uri(
    array('action'=>'showcontextchapters')
)).'">'.$e($this->objLanguage->code2Txt(
    'mod_contextcontent_backtocontent','contextcontent',NULL,
    'Back to [-context-] content'
)).'</a></div></article><style>.contextcontent-section-page{max-width:860px;margin:1rem auto;padding:1.75rem;border:1px solid var(--chisimba-border,#d7dde5);border-top:5px solid var(--chisimba-primary,#0785df);border-radius:12px;background:var(--chisimba-surface,#fff)}.contextcontent-section-page h1{margin:.4rem 0 1rem}.contextcontent-section-page__introduction{font-size:1.05rem;line-height:1.65}.contextcontent-section-page__chapters{margin:1rem 0 1.5rem;padding-left:1.5rem}.contextcontent-section-page__chapters li{padding:.3rem 0}</style>';
?>
