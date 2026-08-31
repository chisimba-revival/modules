<?php
$this->loadClass('htmlheading', 'htmlelements');

$language = function ($key) {
    $fallbacks = array(
        'mod_contextcontent_assessmentpalette' => 'Assessments',
        'mod_contextcontent_addassessment' => 'Add assessment',
    );
    $text = $this->objLanguage->languageText($key, 'contextcontent');
    if (strpos($text, 'Language item not found:') === 0 && isset($fallbacks[$key])) {
        $text = $fallbacks[$key];
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
};
$selectedContentType = isset($selectedContentType) ? (string) $selectedContentType : '';
$hasSelectedType = $selectedContentType !== '';
$activePalette = isset($activePalette) && $activePalette === 'assessment' ? 'assessment' : 'content';
$assessmentGroups = isset($assessmentGroups) ? (array) $assessmentGroups : array();
$aiMode = (string) $this->getParam('ai', '');
$aiAvailable = false;
try {
    $objModules = $this->getObject('modules', 'modulecatalogue');
    if ($objModules->checkIfRegistered('ai')) {
        $objAiService = $this->getObject('aiservice', 'ai');
        $aiAvailable = method_exists($objAiService, 'isAvailable') && $objAiService->isAvailable();
    }
} catch (Throwable $exception) {
    $aiAvailable = false;
}
$hasAiWorkflow = $aiMode !== '' && $aiAvailable;
$objIconService = $this->getObject('iconservice', 'ui');

$heading = new htmlheading();
$heading->type = 1;
$heading->str = $this->objLanguage->languageText('mod_contextcontent_addcontent', 'contextcontent');
echo $heading->show();
?>
<style type="text/css">
.contextcontent-builder{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,360px);gap:1.5rem;align-items:start;margin:1rem 0 2rem}
.contextcontent-canvas{position:relative;display:flex;min-height:560px;align-items:center;justify-content:center;box-sizing:border-box;padding:2rem;border:2px dashed #9aa9b2;border-radius:20px;background:linear-gradient(145deg,#fff 0%,#f6fafb 100%);box-shadow:inset 0 0 0 1px rgba(255,255,255,.8);transition:border-color .18s,background .18s,box-shadow .18s,transform .18s}
.contextcontent-canvas:before{content:"";position:absolute;inset:18px;border:1px solid #e1e8eb;border-radius:13px;pointer-events:none}
.contextcontent-canvas.is-dragover{border-color:#087f5b;background:#edf9f4;box-shadow:0 0 0 5px rgba(8,127,91,.13);transform:scale(1.006)}
.contextcontent-canvas.is-loading{border-style:solid;border-color:#087f5b;background:#f0faf6}
.contextcontent-canvas.has-form{display:block;min-height:560px;padding:2rem 2.25rem;border-style:solid;border-color:#b8c8cf;background:#fff}
.contextcontent-canvas-form{position:relative;z-index:1;width:100%}
.contextcontent-canvas-actions{display:flex;gap:.7rem;justify-content:flex-end;margin:1.25rem 0 0;padding-top:1rem;border-top:1px solid #dce4e8}
.contextcontent-canvas-action{display:inline-block;padding:.62rem .9rem;border:1px solid #b7c5cc;border-radius:7px;background:#fff;color:#29434e;text-decoration:none;font-weight:700}
.contextcontent-canvas-action:hover,.contextcontent-canvas-action:focus{border-color:#087f5b;color:#087f5b;text-decoration:none}
.contextcontent-canvas-empty{position:relative;z-index:1;max-width:520px;text-align:center;color:#52636d}
.contextcontent-canvas-mark{display:flex;width:86px;height:86px;align-items:center;justify-content:center;margin:0 auto 1.1rem;border-radius:24px;background:#e5f4ee;color:#087f5b;box-shadow:0 8px 24px rgba(8,127,91,.12)}
.contextcontent-canvas-mark svg{width:42px;height:42px}
.contextcontent-canvas h2{margin:.2rem 0 .65rem;color:#20343f;font-size:1.65rem}
.contextcontent-canvas p{margin:.25rem auto;line-height:1.55}
.contextcontent-palette{box-sizing:border-box;padding:1.1rem;border:1px solid #dce4e8;border-radius:20px;background:#f7fafb;box-shadow:0 10px 28px rgba(38,50,56,.09)}
.contextcontent-palette-header{margin:0 0 1rem;padding:.2rem .25rem .9rem;border-bottom:1px solid #dce4e8}
.contextcontent-palette-header h2{margin:0 0 .35rem;color:#20343f;font-size:1.35rem}
.contextcontent-palette-header p{margin:0;color:#60727c;font-size:.94rem;line-height:1.45}
.contextcontent-type-cards{display:grid;gap:.75rem}
.contextcontent-type-card{position:relative;display:grid;grid-template-columns:52px 1fr;gap:.85rem;box-sizing:border-box;padding:.9rem;border:1px solid #d7e0e4;border-radius:14px;background:#fff;box-shadow:0 3px 10px rgba(38,50,56,.06);cursor:grab;transition:transform .16s,border-color .16s,box-shadow .16s,opacity .16s}
.contextcontent-type-card:hover,.contextcontent-type-card:focus-within{border-color:#087f5b;box-shadow:0 8px 20px rgba(8,127,91,.14);transform:translateY(-2px)}
.contextcontent-type-card:active{cursor:grabbing}
.contextcontent-type-card.is-selected{border-color:#087f5b;background:#eef9f5;box-shadow:0 0 0 3px rgba(8,127,91,.12)}
.contextcontent-type-card.is-selected:after{content:attr(data-selected-label);position:absolute;right:.65rem;top:.5rem;padding:.18rem .42rem;border-radius:999px;background:#087f5b;color:#fff;font-size:.7rem;font-weight:700}
.contextcontent-type-card.is-inert{opacity:.38;pointer-events:none;transform:none}
.contextcontent-type-icon{display:flex;width:52px;height:52px;align-items:center;justify-content:center;border-radius:14px;background:#eaf3f6;color:#24576c}
.contextcontent-type-card:nth-child(2n) .contextcontent-type-icon{background:#f0ebf8;color:#67478a}
.contextcontent-type-card:nth-child(3n) .contextcontent-type-icon{background:#fff1de;color:#995a11}
.contextcontent-type-icon svg{width:26px;height:26px}
.contextcontent-type-copy h3{margin:0 0 .25rem;color:#263b46;font-size:1rem}
.contextcontent-type-copy p{margin:0;color:#62747d;font-size:.87rem;line-height:1.38}
.contextcontent-type-choice{position:absolute;inset:0;z-index:2;border-radius:14px;text-indent:-9999px;overflow:hidden}
.contextcontent-type-choice:focus{outline:3px solid #087f5b;outline-offset:3px}
.contextcontent-builder-status{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
@media(max-width:900px){.contextcontent-builder{grid-template-columns:1fr}.contextcontent-palette{grid-row:1}.contextcontent-type-cards{grid-template-columns:repeat(2,minmax(0,1fr))}.contextcontent-canvas{min-height:380px}}
@media(max-width:580px){.contextcontent-type-cards{grid-template-columns:1fr}.contextcontent-canvas{min-height:300px;padding:1.25rem}.contextcontent-builder{gap:1rem}.contextcontent-palette{padding:.85rem}}
@media(prefers-reduced-motion:reduce){.contextcontent-canvas,.contextcontent-type-card{transition:none}}
</style>
<div class="contextcontent-builder" id="contextcontent-builder">
    <main class="contextcontent-canvas<?php echo ($hasSelectedType || $hasAiWorkflow) ? ' has-form' : ''; ?>" id="contextcontent-page-canvas" <?php echo ($hasSelectedType || $hasAiWorkflow) ? 'aria-label="'.$language('mod_contextcontent_pagecanvas').'"' : 'aria-labelledby="contextcontent-canvas-title"'; ?>>
<?php if ($hasAiWorkflow): ?>
        <div class="contextcontent-canvas-form" id="contextcontent-live-form">
<?php include dirname(__FILE__) . '/ai_chapter_workflow_tpl.php'; ?>
            <div class="contextcontent-canvas-actions">
                <a class="contextcontent-canvas-action" href="<?php echo htmlspecialchars(str_replace('&amp;', '&', $this->uri(array('action'=>'viewchapter', 'id'=>$chapter))), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $language('mod_contextcontent_cancelpageauthoring'); ?></a>
            </div>
        </div>
<?php elseif ($hasSelectedType): ?>
        <div class="contextcontent-canvas-form" id="contextcontent-live-form">
<?php include dirname(__FILE__) . '/addeditpage_tpl.php'; ?>
            <div class="contextcontent-canvas-actions">
                <a class="contextcontent-canvas-action" href="<?php echo htmlspecialchars(str_replace('&amp;', '&', $this->uri(array('action'=>'viewchapter', 'id'=>$chapter))), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $language('mod_contextcontent_cancelpageauthoring'); ?></a>
            </div>
        </div>
<?php else: ?>
        <div class="contextcontent-canvas-empty">
            <div class="contextcontent-canvas-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
            <h2 id="contextcontent-canvas-title"><?php echo $language('mod_contextcontent_pagecanvas'); ?></h2>
            <p id="contextcontent-canvas-message"><?php echo $language('mod_contextcontent_pagecanvas_empty'); ?></p>
        </div>
<?php endif; ?>
    </main>
    <aside class="contextcontent-palette" aria-labelledby="contextcontent-palette-title">
        <div class="contextcontent-palette-header">
            <nav aria-label="<?php echo $language('mod_contextcontent_palette_tabs'); ?>">
                <a class="button chisimba-button-secondary chisimba-selectable" aria-current="<?php echo $activePalette === 'content' ? 'page' : 'false'; ?>" href="<?php echo htmlspecialchars(str_replace('&amp;', '&', $this->uri(array('action'=>'addpage','chapter'=>$chapter,'palette'=>'content'))), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $language('mod_contextcontent_contentpalette'); ?></a>
                <a class="button chisimba-button-secondary chisimba-selectable" aria-current="<?php echo $activePalette === 'assessment' ? 'page' : 'false'; ?>" href="<?php echo htmlspecialchars(str_replace('&amp;', '&', $this->uri(array('action'=>'addpage','chapter'=>$chapter,'palette'=>'assessment'))), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $language('mod_contextcontent_assessmentpalette'); ?></a>
            </nav>
            <h2 id="contextcontent-palette-title"><?php echo $language($activePalette === 'assessment' ? 'mod_contextcontent_assessmentpalette' : 'mod_contextcontent_contentpalette'); ?></h2>
            <p><?php echo $language(($hasSelectedType || $hasAiWorkflow) ? 'mod_contextcontent_palette_locked' : 'mod_contextcontent_contentpalette_desc'); ?></p>
        </div>
        <div class="contextcontent-type-cards">
<?php if ($activePalette === 'assessment' && !$hasSelectedType): ?>
<?php if ($assessmentGroups === array()): ?>
            <p><?php echo $language('mod_contextcontent_noassessments'); ?></p>
<?php else: foreach ($assessmentGroups as $group): foreach ($group['activities'] as $activity):
    $assessmentUrl = str_replace('&amp;', '&', $this->uri(array(
        'action'=>'addpage', 'chapter'=>$chapter, 'id'=>$parent,
        'contenttype'=>'assessment_activity',
        'providermodule'=>$group['provider']['key'],
        'provideritemid'=>$activity['id']
    )));
    $assessmentIcon = $objIconService->render('clipboard-check', array('decorative'=>TRUE));
?>
            <article class="contextcontent-type-card" draggable="true" data-content-type="assessment-<?php echo htmlspecialchars($activity['id'], ENT_QUOTES, 'UTF-8'); ?>" data-content-url="<?php echo htmlspecialchars($assessmentUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="contextcontent-type-icon" aria-hidden="true"><?php echo $assessmentIcon; ?></div>
                <div class="contextcontent-type-copy"><h3><?php echo htmlspecialchars($activity['name'], ENT_QUOTES, 'UTF-8'); ?></h3><p><?php echo htmlspecialchars($group['provider']['label'], ENT_QUOTES, 'UTF-8'); ?></p></div>
                <a class="contextcontent-type-choice" href="<?php echo htmlspecialchars($assessmentUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo $language('mod_contextcontent_addassessment') . ': ' . htmlspecialchars($activity['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo $language('mod_contextcontent_addassessment'); ?></a>
            </article>
<?php endforeach; endforeach; endif; ?>
<?php else: ?>
<?php if ($aiAvailable && !$hasSelectedType && !$hasAiWorkflow):
    $aiUrl = str_replace('&amp;', '&', $this->uri(array('action'=>'addpage', 'chapter'=>$chapter, 'id'=>$parent, 'ai'=>'start')));
    $aiIcon = $objIconService->render('scroll-text', array('decorative'=>TRUE, 'class'=>'contextcontent-type-icon-svg'));
?>
            <article class="contextcontent-type-card" draggable="false" data-content-type="ai" data-content-url="<?php echo htmlspecialchars($aiUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="contextcontent-type-icon" aria-hidden="true"><?php echo $aiIcon; ?></div>
                <div class="contextcontent-type-copy">
                    <h3><?php echo $language('mod_contextcontent_ai_generate_link'); ?></h3>
                    <p><?php echo $language('mod_contextcontent_ai_generate_desc'); ?></p>
                </div>
                <a class="contextcontent-type-choice" href="<?php echo htmlspecialchars($aiUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo $language('mod_contextcontent_ai_generate_link'); ?>"><?php echo $language('mod_contextcontent_ai_generate_link'); ?></a>
            </article>
<?php endif; ?>
<?php foreach ($contentTypes as $type):
    $key = (string) $type['key'];
    $url = str_replace('&amp;', '&', $this->uri(array('action'=>'addpage', 'chapter'=>$chapter, 'id'=>$parent, 'contenttype'=>$key)));
    $icon = $objIconService->render(
        isset($type['icon']) ? $type['icon'] : 'file-text',
        array('decorative' => TRUE, 'class' => 'contextcontent-type-icon-svg')
    );
?>
            <article class="contextcontent-type-card<?php echo ($hasSelectedType || $hasAiWorkflow) ? ($hasSelectedType && $key === $selectedContentType ? ' is-selected' : ' is-inert') : ''; ?>" draggable="<?php echo ($hasSelectedType || $hasAiWorkflow) ? 'false' : 'true'; ?>" data-selected-label="<?php echo $language('mod_contextcontent_selectedtool'); ?>" data-content-type="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" data-content-url="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="contextcontent-type-icon" aria-hidden="true"><?php echo $icon; ?></div>
                <div class="contextcontent-type-copy">
                    <h3><?php echo htmlspecialchars($type['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars($type['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
<?php if (!$hasSelectedType && !$hasAiWorkflow): ?>
                <a class="contextcontent-type-choice" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo $language('mod_contextcontent_choosecontenttype') . ': ' . htmlspecialchars($type['label'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo $language('mod_contextcontent_choosecontenttype'); ?></a>
<?php endif; ?>
            </article>
<?php endforeach; ?>
<?php endif; ?>
        </div>
    </aside>
    <p class="contextcontent-builder-status" id="contextcontent-builder-status" aria-live="polite"></p>
</div>
<script type="text/javascript">
(function(){
    var builder=document.getElementById('contextcontent-builder');
    var canvas=document.getElementById('contextcontent-page-canvas');
    if(!builder||!canvas){return;}
    var cards=Array.prototype.slice.call(builder.querySelectorAll('.contextcontent-type-card'));
    var message=document.getElementById('contextcontent-canvas-message');
    var status=document.getElementById('contextcontent-builder-status');
    var dropText=<?php echo json_encode($this->objLanguage->languageText('mod_contextcontent_canvas_drop', 'contextcontent')); ?>;
    var loadingText=<?php echo json_encode($this->objLanguage->languageText('mod_contextcontent_canvas_loading', 'contextcontent')); ?>;
    var selectedText=<?php echo json_encode($this->objLanguage->languageText('mod_contextcontent_type_selected', 'contextcontent')); ?>;
    var chosen=<?php echo ($hasSelectedType || $hasAiWorkflow) ? 'true' : 'false'; ?>;
    var liveForm=document.getElementById('contextcontent-live-form');
    var formDirty=false;
    if(liveForm){
        liveForm.addEventListener('input',function(){formDirty=true;});
        liveForm.addEventListener('change',function(){formDirty=true;});
    }
    function choose(card){
        if(chosen||!card){return;}
        chosen=true;
        cards.forEach(function(item){item.classList.toggle('is-selected',item===card);item.classList.toggle('is-inert',item!==card);item.setAttribute('draggable','false');});
        canvas.classList.remove('is-dragover');canvas.classList.add('is-loading');
        if(message){message.textContent=loadingText;}
        var label=card.querySelector('h3');if(status){status.textContent=selectedText+(label?': '+label.textContent:'');}
        window.setTimeout(function(){window.location.href=card.getAttribute('data-content-url');},180);
    }
    cards.forEach(function(card){
        card.addEventListener('dragstart',function(event){if(chosen||card.getAttribute('data-content-type')==='ai'){event.preventDefault();return;}event.dataTransfer.effectAllowed='copy';event.dataTransfer.setData('text/plain',card.getAttribute('data-content-type'));});
        card.addEventListener('dragend',function(){if(message){canvas.classList.remove('is-dragover');message.textContent=<?php echo json_encode($this->objLanguage->languageText('mod_contextcontent_pagecanvas_empty', 'contextcontent')); ?>;}});
        var choice=card.querySelector('a.contextcontent-type-choice');
        if(choice){choice.addEventListener('click',function(event){event.preventDefault();choose(card);});}
    });
    canvas.addEventListener('dragenter',function(event){event.preventDefault();if(!chosen&&message){canvas.classList.add('is-dragover');message.textContent=dropText;}});
    canvas.addEventListener('dragover',function(event){event.preventDefault();event.dataTransfer.dropEffect='copy';});
    canvas.addEventListener('dragleave',function(event){if(message&&!canvas.contains(event.relatedTarget)){canvas.classList.remove('is-dragover');message.textContent=<?php echo json_encode($this->objLanguage->languageText('mod_contextcontent_pagecanvas_empty', 'contextcontent')); ?>;}});
    canvas.addEventListener('drop',function(event){event.preventDefault();if(chosen){return;}var key=event.dataTransfer.getData('text/plain');choose(builder.querySelector('.contextcontent-type-card[data-content-type="'+key.replace(/"/g,'')+'"]'));});
}());
</script>
