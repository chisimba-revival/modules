<?php
$this->appendArrayVar('headerParams', '<style type="text/css">.ingest-table{max-width:100%;margin:1.25rem 0;overflow-x:auto}.ingest-table table{width:100%;border-collapse:collapse}.ingest-table th,.ingest-table td{min-width:8rem;padding:.7rem .8rem;border:1px solid #b0bec5;vertical-align:top;text-align:left}.ingest-table th{background:#eef2f3;font-weight:700}.ingest-table p{margin:.25rem 0}.ingest-table figure{margin:.5rem 0}.ingest-table img{display:block;max-width:100%;height:auto}</style>');
$this->loadClass('link', 'htmlelements');
$objWashout = $this->getObject('washout', 'utilities');
$backLink = new link($this->uri(array('action' => 'showcontextchapters'), 'contextcontent'));
$backLink->link = '&#171; ' . $this->objLanguage->code2Txt(
    'mod_contextcontent_allchapters',
    'contextcontent'
);
$startLink = new link($this->uri(array('action' => 'viewpage', 'id' => $firstPage, 'prevchapterid' => $chapterId), 'contextcontent'));
$startLink->cssClass = 'contextcontent-chapter-start';
$startLabel = $this->objLanguage->code2Txt(
    'mod_contextcontent_startchapter',
    'contextcontent',
    NULL,
    'Start chapter'
);
$startLink->link = $startLabel . ' &#187;';
$itemLabel = ((int)$chapterPageCount === 1) ? $this->objLanguage->languageText('mod_contextcontent_learningitem','contextcontent','learning item') : $this->objLanguage->languageText('mod_contextcontent_learningitems','contextcontent','learning items');
$chapterWord = $this->objLanguage->abstractText('[-chapter-]');
$chapterWordDisplay = ucfirst((string)$chapterWord);
$chapterNumber = 0;
$objContextChapters = $this->getObject('db_contextcontent_contextchapter', 'contextcontent');
$orderedChapters = $objContextChapters->getContextChapters($this->contextCode);
if (is_array($orderedChapters)) {
    $position = 0;
    foreach ($orderedChapters as $orderedChapter) {
        if (!is_array($orderedChapter) || empty($orderedChapter['chapterid'])) {
            continue;
        }
        $position++;
        if ((string)$orderedChapter['chapterid'] === (string)$chapterId) {
            $chapterNumber = $position;
            break;
        }
    }
}
$title = htmlentities($chapter['chaptertitle'], ENT_QUOTES, 'UTF-8');
$chapterHeading = $chapterNumber > 0
    ? htmlentities($chapterWordDisplay, ENT_QUOTES, 'UTF-8') . ' ' . $chapterNumber . ': ' . $title
    : $title;
$introduction = trim((string)$chapter['introduction']);
$this->appendArrayVar('headerParams', '<style type="text/css">.contextcontent-chapter-overview{max-width:900px;margin:2rem auto;padding:0 1rem}.contextcontent-chapter-back{margin-bottom:2rem}.contextcontent-chapter-card{padding:2.4rem 2.6rem;border:1px solid #d9e1e3;border-radius:16px;background:#fff;box-shadow:0 12px 32px rgba(38,50,56,.08)}.contextcontent-chapter-eyebrow{margin:0 0 .5rem;font-size:.9rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#546e7a}.contextcontent-chapter-title{margin:.1rem 0 1rem;font-size:clamp(2rem,5vw,3.5rem);line-height:1.08}.contextcontent-chapter-introduction{font-size:1.12rem;line-height:1.7;color:#37474f}.contextcontent-chapter-meta{margin:1.5rem 0;color:#607d8b;font-weight:700}.contextcontent-chapter-actions{margin-top:2rem}.contextcontent-chapter-start{display:inline-flex;align-items:center;padding:.85rem 1.25rem;border-radius:8px;background:var(--chisimba-primary,#295351);color:#fff!important;text-decoration:none;font-weight:700}.contextcontent-chapter-start:hover,.contextcontent-chapter-start:focus{background:var(--chisimba-primary-hover,#1f4140);color:#fff!important}@media(max-width:600px){.contextcontent-chapter-overview{margin:1rem auto;padding:0 .5rem}.contextcontent-chapter-card{padding:1.6rem 1.35rem;border-radius:12px}}</style>');
echo '<div id="context_content" class="contextcontent-chapter-overview"><div class="contextcontent-chapter-back">'.$backLink->show().'</div><section class="contextcontent-chapter-card"><p class="contextcontent-chapter-eyebrow">'.htmlentities($chapterWordDisplay,ENT_QUOTES,'UTF-8').'</p><h1 class="contextcontent-chapter-title">'.$chapterHeading.'</h1>';
if ($introduction !== '') echo '<div class="contextcontent-chapter-introduction">'.$objWashout->parseText($introduction).'</div>';
echo '<p class="contextcontent-chapter-meta">'.(int)$chapterPageCount.' '.htmlentities($itemLabel,ENT_QUOTES,'UTF-8').'</p><div class="contextcontent-chapter-actions">'.$startLink->show().'</div></section>';
if (!empty($canManageCourse) && is_array($chapterPages) && count($chapterPages) > 1) {
    $escape = function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
    echo '<section class="contextcontent-order-card" aria-labelledby="contextcontent-order-heading">'
        . '<h2 id="contextcontent-order-heading">Reorder learning items</h2>'
        . '<p>Drag items into the required order, or use the Move up and Move down buttons. Save when finished.</p>'
        . '<form method="post" action="'.$escape(str_replace('&amp;', '&', $this->uri(array('action'=>'savepageorder')))).'">'
        . '<input type="hidden" name="csrf_token" value="'.$escape($contextContentCsrf).'" />'
        . '<input type="hidden" name="chapter" value="'.$escape($chapterId).'" />'
        . '<input type="hidden" id="contextcontent-page-order" name="page_order" value="" />'
        . '<ol class="contextcontent-order-list" id="contextcontent-order-list">';
    foreach ($chapterPages as $page) {
        if (($page['parentid'] ?? 'root') !== 'root') { continue; }
        echo '<li draggable="true" data-page-id="'.$escape($page['id']).'">'
            . '<span class="contextcontent-order-handle" aria-hidden="true">&#x2630;</span>'
            . '<span class="contextcontent-order-title">'.$escape($page['menutitle']).'</span>'
            . '<span class="contextcontent-order-buttons"><button type="button" class="button contextcontent-order-up">Move up</button> '
            . '<button type="button" class="button contextcontent-order-down">Move down</button></span></li>';
    }
    echo '</ol><button type="submit" class="button">Save order</button></form></section>';
}
echo '</div>';
?>
<style type="text/css">
.contextcontent-order-card{margin:1.5rem 0;padding:1.5rem;border:1px solid #d9e1e3;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(38,50,56,.07)}
.contextcontent-order-list{margin:1rem 0;padding:0;list-style:none}.contextcontent-order-list li{display:flex;gap:.8rem;align-items:center;margin:.55rem 0;padding:.8rem;border:1px solid #d9e1e3;border-radius:10px;background:#f8fafb}.contextcontent-order-list li.is-dragging{opacity:.45}.contextcontent-order-handle{cursor:grab;font-size:1.25rem}.contextcontent-order-title{flex:1;font-weight:700}.contextcontent-order-buttons{white-space:nowrap}@media(max-width:650px){.contextcontent-order-list li{align-items:flex-start;flex-wrap:wrap}.contextcontent-order-buttons{width:100%;padding-left:2rem}}
</style>
<script type="text/javascript">
(function(){var list=document.getElementById('contextcontent-order-list'),input=document.getElementById('contextcontent-page-order');if(!list||!input){return;}var dragging=null;function items(){return Array.prototype.slice.call(list.querySelectorAll('li[data-page-id]'));}function sync(){var rows=items();input.value=rows.map(function(row){return row.getAttribute('data-page-id');}).join(',');rows.forEach(function(row,index){row.querySelector('.contextcontent-order-up').disabled=index===0;row.querySelector('.contextcontent-order-down').disabled=index===rows.length-1;});}list.addEventListener('dragstart',function(event){dragging=event.target.closest('li[data-page-id]');if(!dragging){return;}dragging.classList.add('is-dragging');event.dataTransfer.effectAllowed='move';});list.addEventListener('dragend',function(){if(dragging){dragging.classList.remove('is-dragging');}dragging=null;sync();});list.addEventListener('dragover',function(event){event.preventDefault();if(!dragging){return;}var target=event.target.closest('li[data-page-id]');if(!target||target===dragging){return;}var rect=target.getBoundingClientRect();list.insertBefore(dragging,event.clientY<rect.top+rect.height/2?target:target.nextSibling);});list.addEventListener('click',function(event){var row=event.target.closest('li[data-page-id]');if(!row){return;}if(event.target.classList.contains('contextcontent-order-up')&&row.previousElementSibling){list.insertBefore(row,row.previousElementSibling);}if(event.target.classList.contains('contextcontent-order-down')&&row.nextElementSibling){list.insertBefore(row.nextElementSibling,row);}sync();});sync();}());
</script>
