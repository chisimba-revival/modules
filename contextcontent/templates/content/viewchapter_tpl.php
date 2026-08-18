<?php
$this->loadClass('link', 'htmlelements');
$objWashout = $this->getObject('washout', 'utilities');
$backLink = new link($this->uri(array('action' => 'showcontextchapters'), 'contextcontent'));
$backLink->link = '&#171; ' . $this->objLanguage->languageText('mod_contextcontent_allchapters', 'contextcontent', 'All chapters');
$startLink = new link($this->uri(array('action' => 'viewpage', 'id' => $firstPage, 'prevchapterid' => $chapterId), 'contextcontent'));
$startLink->cssClass = 'contextcontent-chapter-start';
$startLink->link = $this->objLanguage->languageText('mod_contextcontent_startchapter', 'contextcontent', 'Start chapter') . ' &#187;';
$itemLabel = ((int)$chapterPageCount === 1) ? $this->objLanguage->languageText('mod_contextcontent_learningitem','contextcontent','learning item') : $this->objLanguage->languageText('mod_contextcontent_learningitems','contextcontent','learning items');
$chapterWord = $this->objLanguage->languageText('word_chapter', 'word', 'Chapter');
$title = htmlentities($chapter['chaptertitle'], ENT_QUOTES, 'UTF-8');
$introduction = trim((string)$chapter['introduction']);
$this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-chapter-overview{max-width:900px;margin:2rem auto;padding:0 1rem}.contextcontent-chapter-back{margin-bottom:2rem}.contextcontent-chapter-card{padding:2.4rem 2.6rem;border:1px solid #d9e1e3;border-radius:16px;background:#fff;box-shadow:0 12px 32px rgba(38,50,56,.08)}.contextcontent-chapter-eyebrow{margin:0 0 .5rem;font-size:.9rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#546e7a}.contextcontent-chapter-title{margin:.1rem 0 1rem;font-size:clamp(2rem,5vw,3.5rem);line-height:1.08}.contextcontent-chapter-introduction{font-size:1.12rem;line-height:1.7;color:#37474f}.contextcontent-chapter-meta{margin:1.5rem 0;color:#607d8b;font-weight:700}.contextcontent-chapter-actions{margin-top:2rem}.contextcontent-chapter-start{display:inline-flex;align-items:center;padding:.85rem 1.25rem;border-radius:8px;background:#295351;color:#fff!important;text-decoration:none;font-weight:700}.contextcontent-chapter-start:hover,.contextcontent-chapter-start:focus{background:#1f4140;color:#fff!important}@media(max-width:600px){.contextcontent-chapter-overview{margin:1rem auto;padding:0 .5rem}.contextcontent-chapter-card{padding:1.6rem 1.35rem;border-radius:12px}}</style>');
echo '<div id="context_content" class="contextcontent-chapter-overview"><div class="contextcontent-chapter-back">'.$backLink->show().'</div><section class="contextcontent-chapter-card"><p class="contextcontent-chapter-eyebrow">'.htmlentities($chapterWord,ENT_QUOTES,'UTF-8').'</p><h1 class="contextcontent-chapter-title">'.$title.'</h1>';
if ($introduction !== '') echo '<div class="contextcontent-chapter-introduction">'.$objWashout->parseText($introduction).'</div>';
echo '<p class="contextcontent-chapter-meta">'.(int)$chapterPageCount.' '.htmlentities($itemLabel,ENT_QUOTES,'UTF-8').'</p><div class="contextcontent-chapter-actions">'.$startLink->show().'</div></section></div>';
?>
