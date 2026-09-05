<?php
$e=function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$wash=$this->getObject('washout','utilities'); $icons=$this->getObject('iconservice','ui');
$sectionLabel=$this->objLanguage->code2Txt('mod_contextcontent_sectionlabel','contextcontent',NULL,'[-section-]');
$locked=$this->objLanguage->code2Txt('mod_contextcontent_sectionlocked','contextcontent',NULL,'Complete the previous [-section-] to continue.');
$ack=$this->objLanguage->code2Txt('mod_contextcontent_acknowledgesection','contextcontent',NULL,'I have read this [-section-] introduction');
echo '<div class="chisimba-page-title-row"><h1>'.$e($this->objContext->getTitle()).'</h1>';
if (!empty($canManageSections)) {
  $manageUrl=$this->uri(array('action'=>'managesections'));
  echo '<span class="contextcontent-section-actions"><a class="chisimba-button chisimba-button-primary" href="'.$e($manageUrl).'">'.$e($this->objLanguage->code2Txt('mod_contextcontent_organisesections','contextcontent',NULL,'Organise [-sections-] and [-chapters-]')).'</a><a class="chisimba-button chisimba-button-secondary" href="'.$e($manageUrl).'#add-section">'.$e($this->objLanguage->code2Txt('mod_contextcontent_addsection','contextcontent',NULL,'Add [-section-]')).'</a></span>';
}
echo '</div><div class="contextcontent-sections">';
foreach ($sections as $index=>$section) {
  echo '<section class="contextcontent-section-card'.(empty($section['available'])?' is-locked':'').'"><header><span class="contextcontent-section-marker">'.$icons->render(empty($section['available'])?'lock':'layers',array('decorative'=>TRUE)).' '.$e(ucfirst($sectionLabel)).' '.($index+1).'</span><h2>'.$e($section['title']).'</h2></header>';
  if (empty($section['available'])) { echo '<p class="contextcontent-section-state">'.$e($locked).'</p></section>'; continue; }
  echo '<div class="contextcontent-section-introduction">'.$wash->parseText($section['introduction']).'</div>';
  if (empty($section['acknowledged'])) {
    echo '<form method="post" action="'.$e($this->uri(array('action'=>'acknowledgesection'))).'"><input type="hidden" name="csrf_token" value="'.$e($contextContentCsrf).'" /><input type="hidden" name="sectionid" value="'.$e($section['id']).'" /><button class="chisimba-button chisimba-button-primary" type="submit">'.$e($ack).'</button></form></section>'; continue;
  }
  echo '<div class="contextcontent-section-chapters">';
  foreach ($section['chapters'] as $chapter) { echo '<a class="contextcontent-section-chapter" href="'.$e($this->uri(array('action'=>'viewchapter','id'=>$chapter['chapterid']))).'">'.$icons->render('book-open',array('decorative'=>TRUE)).'<span><strong>'.$e($chapter['chaptertitle']).'</strong><small>'.$e($chapter['pagecount']).' '.$e($this->objLanguage->languageText('mod_contextcontent_pages','contextcontent','pages')).'</small></span></a>'; }
  echo '</div></section>';
}
echo '</div><style>.contextcontent-section-actions{display:flex;flex-wrap:wrap;gap:.55rem}.contextcontent-sections{display:grid;gap:1rem}.contextcontent-section-card{border:1px solid var(--chisimba-border,#d7dde5);border-top:4px solid var(--chisimba-primary,#0785df);border-radius:12px;padding:1.25rem;background:var(--chisimba-surface,#fff)}.contextcontent-section-card.is-locked{opacity:.68;border-top-color:#77808d}.contextcontent-section-marker{display:flex;gap:.45rem;align-items:center;color:var(--chisimba-primary,#0675c9);font-weight:700;text-transform:uppercase;font-size:.78rem;letter-spacing:.05em}.contextcontent-section-card h2{margin:.35rem 0 .75rem}.contextcontent-section-chapters{display:grid;gap:.6rem;margin-top:1rem}.contextcontent-section-chapter{display:flex;gap:.75rem;align-items:center;padding:.85rem;border:1px solid var(--chisimba-border,#d7dde5);border-radius:9px;text-decoration:none}.contextcontent-section-chapter:hover{border-color:var(--chisimba-primary,#0785df);background:var(--chisimba-surface-muted,#f5f8fb)}.contextcontent-section-chapter span{display:flex;flex-direction:column}.contextcontent-section-chapter small{color:#667085}</style>';
?>
