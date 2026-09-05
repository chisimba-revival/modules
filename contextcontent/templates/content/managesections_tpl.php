<?php
$e=function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$txt=function($key,$fallback){return $this->objLanguage->languageText($key,'contextcontent',$fallback);};
$code=function($key,$fallback){return $this->objLanguage->code2Txt($key,'contextcontent',NULL,$fallback);};
$title=$code('mod_contextcontent_organisesections','Organise [-sections-] and [-chapters-]');
$editing=is_array($editSection);
$sectionChapterCounts=array(); foreach((array)$chapters as $chapter){$sid=(string)($chapter['sectionid']??'');$sectionChapterCounts[$sid]=($sectionChapterCounts[$sid]??0)+1;}
$editor=$this->newObject('htmlarea','htmlelements');
$editor->name='introduction';
$editor->context=TRUE;
$editor->value=$editing?(string)$editSection['introduction']:'';
$icons=$this->getObject('iconservice','ui');
$icon=function($name) use ($icons){return $icons->render($name,array('decorative'=>TRUE,'class'=>'chisimba-action-icon'));};
$editIcon=$icon('pencil'); $upIcon=$icon('chevron-up'); $downIcon=$icon('chevron-down');
$deleteIcon=$icon('trash-2'); $moveIcon=$icon('arrow-right-left'); $addIcon=$icon('plus');
$viewIcon=$icon('eye'); $saveIcon=$icon('save');

echo '<div class="chisimba-page-title-row"><h1>'.$e($title).'</h1><span class="contextcontent-section-actions">'
    .'<a class="button" href="'.$e($this->uri(array('action'=>'addchapter'))).'">'.$addIcon.'<span>'.$e($code('mod_contextcontent_addchapter','Add a new [-chapter-]')).'</span></a>'
    .'<a class="button chisimba-button-secondary" href="'.$e($this->uri(array('action'=>'showcontextchapters'))).'">'.$viewIcon.'<span>'.$e($txt('mod_contextcontent_viewcontent','View content')).'</span></a></span></div>';
echo '<p class="contextcontent-section-help">'.$e($code('mod_contextcontent_sectionmanagerhelp','Create the [-sections-] first, then place each [-chapter-] in the appropriate [-section-]. You can move a [-chapter-] at any time.')).'</p>';

echo '<details id="section-editor" class="contextcontent-section-editor"'.($editing?' open':'').'><summary>'.$addIcon.'<span>'.$e($editing?$code('mod_contextcontent_editsection','Edit [-section-]'):$code('mod_contextcontent_addsection','Add [-section-]')).'</span></summary><div class="contextcontent-section-editor-body">'
    .'<form method="post" action="'.$e($this->uri(array('action'=>'savesection'))).'">'
    .'<input type="hidden" name="csrf_token" value="'.$e($contextContentCsrf).'" />'
    .($editing?'<input type="hidden" name="sectionid" value="'.$e($editSection['id']).'" />':'')
    .'<input type="hidden" name="visibility" value="Y" />'
    .'<label class="contextcontent-field">'.$e($this->objLanguage->languageText('word_title','system','Title')).'<input required maxlength="255" name="title" value="'.$e($editing?$editSection['title']:'').'" /></label>'
    .'<label class="contextcontent-field">'.$e($txt('mod_contextcontent_sectionintroduction','Introduction')).'</label>'.$editor->show()
    .'<div class="contextcontent-form-actions"><button class="button" type="submit">'.($editing?$saveIcon:$addIcon).'<span>'.$e($editing?$this->objLanguage->languageText('word_save','system','Save'):$this->objLanguage->languageText('word_add','system','Add')).'</span></button>'
    .($editing?'<a class="button chisimba-button-secondary" href="'.$e($this->uri(array('action'=>'managesections'))).'">'.$e($this->objLanguage->languageText('word_cancel','system','Cancel')).'</a>':'').'</div></form></div></details>';

echo '<section class="contextcontent-manager-card"><h2>'.$e($code('mod_contextcontent_currentsections','Current [-sections-]')).'</h2><ol class="contextcontent-manage-list">';
foreach($sections as $index=>$section){
    echo '<li><strong><span class="contextcontent-row-type">'.$e(ucfirst($code('mod_contextcontent_sectionlabel','[-section-]'))).' '.($index+1).'</span>'.$e($section['title']).'</strong><span class="contextcontent-row-actions">'
        .'<a class="chisimba-icon-button" aria-label="'.$e($this->objLanguage->languageText('word_edit','system','Edit')).'" title="'.$e($this->objLanguage->languageText('word_edit','system','Edit')).'" href="'.$e($this->uri(array('action'=>'managesections','editsection'=>$section['id']))).'#section-editor">'.$editIcon.'</a>';
    foreach(array('up','down') as $direction){
        $disabled=($direction==='up'&&$index===0)||($direction==='down'&&$index===count($sections)-1);
        $moveLabel=$direction==='up'?$txt('mod_contextcontent_moveup','Move up'):$txt('mod_contextcontent_movedown','Move down');
        echo '<form method="post" action="'.$e($this->uri(array('action'=>$direction==='up'?'movesectionup':'movesectiondown'))).'"><input type="hidden" name="csrf_token" value="'.$e($contextContentCsrf).'" /><input type="hidden" name="sectionid" value="'.$e($section['id']).'" /><button class="chisimba-icon-button" aria-label="'.$e($moveLabel).'" title="'.$e($moveLabel).'" type="submit"'.($disabled?' disabled':'').'>'.($direction==='up'?$upIcon:$downIcon).'</button></form>';
    }
    $hasChapters=!empty($sectionChapterCounts[$section['id']]);
    if(!$hasChapters){$deleteLabel=$code('mod_contextcontent_deletesection','Delete [-section-]');echo '<form method="post" action="'.$e($this->uri(array('action'=>'deletesection'))).'"><input type="hidden" name="csrf_token" value="'.$e($contextContentCsrf).'" /><input type="hidden" name="sectionid" value="'.$e($section['id']).'" /><button class="chisimba-icon-button chisimba-icon-button-danger" aria-label="'.$e($deleteLabel).'" title="'.$e($deleteLabel).'" type="submit">'.$deleteIcon.'</button></form>';}
    echo '</span></li>';
}
echo '</ol></section>';

echo '<section class="contextcontent-manager-card"><h2>'.$e(ucfirst($code('mod_contextcontent_chaptersbysection','[-chapters-] by [-section-]'))).'</h2><p>'.$e($code('mod_contextcontent_chaptersbysectionhelp','Each card below is a [-chapter-]. Choose Move to [-section-] only when the [-chapter-] belongs in a different [-section-].')).'</p>';
$groups=array(); foreach($sections as $section){$groups[$section['id']]=array('section'=>$section,'chapters'=>array());}
$groups['']=array('section'=>FALSE,'chapters'=>array());
foreach($chapters as $chapter){$sid=(string)($chapter['sectionid']??'');if(!isset($groups[$sid])){$sid='';}$groups[$sid]['chapters'][]=$chapter;}
$sectionNumber=0;
foreach($groups as $sid=>$group){
    if($sid===''&&empty($group['chapters'])){continue;}
    $isUnassigned=$sid===''; if(!$isUnassigned){$sectionNumber++;}
    $groupTitle=$isUnassigned?$code('mod_contextcontent_unassignedchapters','Unassigned [-chapters-]'):$group['section']['title'];
    $groupLabel=$isUnassigned?$code('mod_contextcontent_notinsection','Not in a [-section-]'):ucfirst($code('mod_contextcontent_sectionlabel','[-section-]')).' '.$sectionNumber;
    echo '<section class="contextcontent-chapter-group"><header><span class="contextcontent-row-type">'.$e($groupLabel).'</span><h3>'.$e($groupTitle).'</h3></header>';
    if(empty($group['chapters'])){echo '<p class="contextcontent-empty-group">'.$e($code('mod_contextcontent_nochaptersinsection','No [-chapters-] are currently in this [-section-].')).'</p></section>';continue;}
    foreach($group['chapters'] as $position=>$chapter){
        echo '<article class="contextcontent-chapter-placement"><div class="contextcontent-chapter-identity"><span class="contextcontent-row-type">'.$e(ucfirst($code('mod_contextcontent_chapterlabel','[-chapter-]'))).' '.($position+1).'</span><strong>'.$e($chapter['chaptertitle']).'</strong></div>'
            .'<form class="contextcontent-placement-form" method="post" action="'.$e($this->uri(array('action'=>'assignsection'))).'"><input type="hidden" name="csrf_token" value="'.$e($contextContentCsrf).'" /><input type="hidden" name="id" value="'.$e($chapter['contextchapterid']).'" /><label><span>'.$e($code('mod_contextcontent_movetosection','Move to [-section-]')).'</span><select required name="sectionid"><option value="">'.$e($txt('mod_contextcontent_choosedestination','Choose destination')).'</option>';
        if(!$isUnassigned){echo '<option value="__unassigned__">'.$e($txt('mod_contextcontent_unassigned','Not assigned')).'</option>';}
        foreach($sections as $destination){if($destination['id']===$sid){continue;}echo '<option value="'.$e($destination['id']).'">'.$e($destination['title']).'</option>';}
        echo '</select></label><button class="button" type="submit">'.$moveIcon.'<span>'.$e($code('mod_contextcontent_movechapter','Move [-chapter-]')).'</span></button></form>';
        echo '</article>';
    }
    echo '</section>';
}
echo '</section>';
?>
<style>
.contextcontent-section-actions,.contextcontent-form-actions,.contextcontent-row-actions{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}.contextcontent-section-actions .button,.contextcontent-form-actions .button,.contextcontent-placement-form .button{display:inline-flex;align-items:center;gap:.4rem}.contextcontent-section-help{padding:1rem;background:var(--chisimba-surface-muted,#f5f8fb);border-radius:10px}.contextcontent-section-editor{margin:1rem 0;border:1px solid var(--chisimba-border,#d7dde5);border-radius:12px;background:var(--chisimba-surface,#fff);overflow:hidden}.contextcontent-section-editor>summary{display:flex;align-items:center;gap:.45rem;padding:.9rem 1.1rem;color:var(--chisimba-link,#0879c9);font-weight:750;cursor:pointer;list-style:none}.contextcontent-section-editor>summary::-webkit-details-marker{display:none}.contextcontent-section-editor>summary::after{content:'›';margin-left:auto;font-size:1.35rem;line-height:1;transform:rotate(90deg);transition:transform .15s ease}.contextcontent-section-editor[open]>summary{border-bottom:1px solid var(--chisimba-border,#d7dde5)}.contextcontent-section-editor[open]>summary::after{transform:rotate(-90deg)}.contextcontent-section-editor>summary:hover{background:var(--chisimba-surface-muted,#f5f8fb)}.contextcontent-section-editor-body{padding:1.25rem}.contextcontent-manager-card{margin:1rem 0;padding:1.25rem;border:1px solid var(--chisimba-border,#d7dde5);border-radius:12px;background:var(--chisimba-surface,#fff)}.contextcontent-field{display:grid;gap:.35rem;margin-bottom:1rem;font-weight:700}.contextcontent-field input{box-sizing:border-box;width:100%;max-width:45rem}.contextcontent-manage-list{list-style:none;padding:0}.contextcontent-manage-list li{display:grid;grid-template-columns:minmax(180px,1fr) auto;gap:.75rem;align-items:center;margin:.6rem 0;padding:.85rem;border:1px solid var(--chisimba-border,#d7dde5);border-radius:10px;background:var(--chisimba-surface-muted,#f8fafb)}.contextcontent-row-type{display:block;margin-bottom:.18rem;color:var(--chisimba-text-muted,#667085);font-size:.75rem;font-weight:750;letter-spacing:.055em;text-transform:uppercase}.contextcontent-row-actions form{margin:0}.contextcontent-row-actions .chisimba-icon-button{padding:0}.chisimba-icon-button-danger{color:var(--chisimba-danger)}.chisimba-icon-button:disabled{cursor:default;opacity:.35}.contextcontent-chapter-group{margin:1rem 0;padding:1rem;border:1px solid var(--chisimba-border,#d7dde5);border-radius:11px;background:var(--chisimba-surface-subtle,#f8fafb)}.contextcontent-chapter-group>header h3{margin:.15rem 0 .8rem}.contextcontent-empty-group{color:var(--chisimba-text-muted,#667085)}.contextcontent-chapter-placement{display:grid;grid-template-columns:minmax(180px,1fr) minmax(270px,1.5fr);gap:1rem;align-items:end;margin:.65rem 0;padding:1rem;border:1px solid var(--chisimba-border,#d7dde5);border-radius:10px;background:var(--chisimba-surface,#fff)}.contextcontent-chapter-identity strong{display:block}.contextcontent-placement-form{display:flex;gap:.55rem;align-items:end}.contextcontent-placement-form label{display:grid;gap:.25rem;flex:1;font-weight:650}.contextcontent-placement-form select{min-width:190px;width:100%}@media(max-width:900px){.contextcontent-chapter-placement{grid-template-columns:1fr}.contextcontent-placement-form{grid-column:1}}@media(max-width:600px){.contextcontent-manage-list li{grid-template-columns:1fr}.contextcontent-placement-form{align-items:stretch;flex-direction:column}}
</style>
