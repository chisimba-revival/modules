<?php
$e=static function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$icons=$this->getObject('iconservice','ui');
$sectionLabel=$this->objLanguage->code2Txt(
    'mod_contextcontent_sectionlabel','contextcontent',NULL,'[-section-]'
);
$openLabel=$this->objLanguage->code2Txt(
    'mod_contextcontent_opensection','contextcontent',NULL,'Open [-section-]'
);
echo '<div class="chisimba-page-title-row"><h1>'.$e($this->objContext->getTitle()).'</h1>';
if (!empty($canManageSections)) {
    $manageUrl=$this->uri(array('action'=>'managesections'));
    echo '<span class="contextcontent-section-actions"><a class="chisimba-button chisimba-button-primary" href="'
        .$e($manageUrl).'">'.$e($this->objLanguage->code2Txt(
            'mod_contextcontent_organisesections','contextcontent',NULL,
            'Organise [-sections-] and [-chapters-]'
        )).'</a></span>';
}
echo '</div><div class="contextcontent-sections">';
foreach ($sections as $index=>$section) {
    echo '<section class="contextcontent-section-card"><header><span class="contextcontent-section-marker">'
        .$icons->render('layers',array('decorative'=>TRUE)).' '
        .$e(ucfirst($sectionLabel)).' '.($index+1).'</span><h2>'.$e($section['title']).'</h2></header>'
        .'<div class="contextcontent-section-chapters">';
    foreach ($section['chapters'] as $chapter) {
        echo '<div class="contextcontent-section-chapter">'.$icons->render(
            'book-open',array('decorative'=>TRUE)
        ).'<span><strong>'.$e($chapter['chaptertitle']).'</strong><small>'
            .$e($chapter['pagecount']).' '.$e($this->objLanguage->languageText(
                'mod_contextcontent_pages','contextcontent','pages'
            )).'</small></span></div>';
    }
    echo '</div><a class="chisimba-button chisimba-button-primary" href="'.$e($this->uri(
        array('action'=>'viewsection','id'=>$section['id'])
    )).'">'.$e($openLabel).'</a></section>';
}
echo '</div><style>.contextcontent-section-actions{display:flex;flex-wrap:wrap;gap:.55rem}.contextcontent-sections{display:grid;gap:1rem}.contextcontent-section-card{border:1px solid var(--chisimba-border,#d7dde5);border-top:4px solid var(--chisimba-primary,#0785df);border-radius:12px;padding:1.25rem;background:var(--chisimba-surface,#fff)}.contextcontent-section-marker{display:flex;gap:.45rem;align-items:center;color:var(--chisimba-primary,#0675c9);font-weight:700;text-transform:uppercase;font-size:.78rem;letter-spacing:.05em}.contextcontent-section-card h2{margin:.35rem 0 .75rem}.contextcontent-section-chapters{display:grid;gap:.6rem;margin:1rem 0}.contextcontent-section-chapter{display:flex;gap:.75rem;align-items:center;padding:.85rem;border:1px solid var(--chisimba-border,#d7dde5);border-radius:9px}.contextcontent-section-chapter span{display:flex;flex-direction:column}.contextcontent-section-chapter small{color:#667085}</style>';
?>
