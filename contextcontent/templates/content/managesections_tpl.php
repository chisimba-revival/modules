<?php
$e=static function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$txt=function($key,$fallback){return $this->objLanguage->languageText($key,'contextcontent',$fallback);};
$code=function($key,$fallback){return $this->objLanguage->code2Txt($key,'contextcontent',NULL,$fallback);};
$editing=is_array($editSection);
$counts=array();
foreach((array)$chapters as $chapter){
    $sectionId=(string)($chapter['sectionid']??'');
    $counts[$sectionId]=($counts[$sectionId]??0)+1;
}
$editor=$this->newObject('htmlarea','htmlelements');
$editor->name='introduction';
$editor->context=TRUE;
$editor->value=$editing?(string)$editSection['introduction']:'';
$icons=$this->getObject('iconservice','ui');
$icon=static function($name) use($icons){
    return $icons->render($name,array('decorative'=>TRUE,'class'=>'chisimba-action-icon'));
};

echo '<div class="chisimba-page-title-row"><h1>'.$e($code(
    'mod_contextcontent_organisesections','Organise [-sections-] and [-chapters-]'
)).'</h1><span class="contextcontent-section-actions"><a class="button chisimba-button-secondary" href="'
    .$e($this->uri(array('action'=>'showcontextchapters'))).'">'.$icon('eye').'<span>'
    .$e($txt('mod_contextcontent_viewcontent','View content')).'</span></a></span></div>';
echo '<p class="contextcontent-section-help">'.$e($code(
    'mod_contextcontent_sectionmanagerhelp',
    'Create informational [-sections-], then assign each [-chapter-] to one. [-chapter-] order remains managed with the standard [-chapter-] controls.'
)).'</p>';

echo '<details id="section-editor" class="contextcontent-section-editor"'.($editing?' open':'').'><summary>'
    .$icon($editing?'pencil':'plus').'<span>'.$e($editing
        ?$code('mod_contextcontent_editsection','Edit [-section-]')
        :$code('mod_contextcontent_addsection','Add [-section-]'))
    .'</span></summary><div class="contextcontent-section-editor-body"><form method="post" action="'
    .$e($this->uri(array('action'=>'savesection'))).'"><input type="hidden" name="csrf_token" value="'
    .$e($contextContentCsrf).'" />'.($editing
        ?'<input type="hidden" name="sectionid" value="'.$e($editSection['id']).'" />':'')
    .'<input type="hidden" name="visibility" value="Y" /><label class="contextcontent-field">'
    .$e($this->objLanguage->languageText('word_title','system','Title'))
    .'<input required maxlength="255" name="title" value="'.$e($editing?$editSection['title']:'')
    .'" /></label><label class="contextcontent-field">'.$e($txt(
        'mod_contextcontent_sectionintroduction','Introduction'
    )).'</label>'.$editor->show().'<div class="contextcontent-form-actions"><button class="button" type="submit">'
    .$icon($editing?'save':'plus').'<span>'.$e($editing
        ?$this->objLanguage->languageText('word_save','system','Save')
        :$this->objLanguage->languageText('word_add','system','Add'))
    .'</span></button>'.($editing?'<a class="button chisimba-button-secondary" href="'
        .$e($this->uri(array('action'=>'managesections'))).'">'
        .$e($this->objLanguage->languageText('word_cancel','system','Cancel')).'</a>':'')
    .'</div></form></div></details>';

echo '<section class="contextcontent-manager-card"><h2>'.$e($code(
    'mod_contextcontent_currentsections','Current [-sections-]'
)).'</h2><ul class="contextcontent-manage-list">';
foreach($sections as $section){
    echo '<li><strong>'.$e($section['title']).'</strong><span class="contextcontent-row-actions"><a class="chisimba-icon-button" aria-label="'
        .$e($this->objLanguage->languageText('word_edit','system','Edit')).'" title="'
        .$e($this->objLanguage->languageText('word_edit','system','Edit')).'" href="'
        .$e($this->uri(array('action'=>'managesections','editsection'=>$section['id'])))
        .'#section-editor">'.$icon('pencil').'</a>';
    if(empty($counts[$section['id']])){
        $deleteLabel=$code('mod_contextcontent_deletesection','Delete [-section-]');
        echo '<form method="post" action="'.$e($this->uri(array('action'=>'deletesection')))
            .'"><input type="hidden" name="csrf_token" value="'.$e($contextContentCsrf)
            .'" /><input type="hidden" name="sectionid" value="'.$e($section['id'])
            .'" /><button class="chisimba-icon-button chisimba-icon-button-danger" aria-label="'
            .$e($deleteLabel).'" title="'.$e($deleteLabel).'" type="submit">'
            .$icon('trash-2').'</button></form>';
    }
    echo '</span></li>';
}
echo '</ul></section>';

echo '<section class="contextcontent-manager-card"><h2>'.$e($code(
    'mod_contextcontent_assignchapters','Assign [-chapters-]'
)).'</h2><p>'.$e($code(
    'mod_contextcontent_chapterassignmenthelp',
    'Assignment adds the informational [-section-] to the journey. It does not change [-chapter-] order.'
)).'</p><div class="contextcontent-assignment-list">';
foreach($chapters as $position=>$chapter){
    $current=(string)($chapter['sectionid']??'');
    echo '<article class="contextcontent-chapter-placement"><div><span class="contextcontent-row-type">'
        .$e(ucfirst($code('mod_contextcontent_chapterlabel','[-chapter-]'))).' '.($position+1)
        .'</span><strong>'.$e($chapter['chaptertitle']).'</strong></div><form class="contextcontent-placement-form" method="post" action="'
        .$e($this->uri(array('action'=>'assignsection'))).'"><input type="hidden" name="csrf_token" value="'
        .$e($contextContentCsrf).'" /><input type="hidden" name="id" value="'
        .$e($chapter['contextchapterid']).'" /><label><span>'.$e($code(
            'mod_contextcontent_sectionlabel','[-section-]'
        )).'</span><select name="sectionid"><option value="__unassigned__">'
        .$e($txt('mod_contextcontent_unassigned','Not assigned')).'</option>';
    foreach($sections as $section){
        echo '<option value="'.$e($section['id']).'"'.($current===$section['id']?' selected':'').'>'
            .$e($section['title']).'</option>';
    }
    echo '</select></label><button class="button" type="submit">'.$icon('save').'<span>'
        .$e($this->objLanguage->languageText('word_save','system','Save')).'</span></button></form></article>';
}
echo '</div></section>';
?>
<style>
.contextcontent-section-actions,.contextcontent-form-actions,.contextcontent-row-actions{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}.contextcontent-section-actions .button,.contextcontent-form-actions .button,.contextcontent-placement-form .button{display:inline-flex;align-items:center;gap:.4rem}.contextcontent-section-help{padding:1rem;background:var(--chisimba-surface-muted,#f5f8fb);border-radius:10px}.contextcontent-section-editor{margin:1rem 0;border:1px solid var(--chisimba-border,#d7dde5);border-radius:12px;background:var(--chisimba-surface,#fff);overflow:hidden}.contextcontent-section-editor>summary{display:flex;align-items:center;gap:.45rem;padding:.9rem 1.1rem;color:var(--chisimba-link,#0879c9);font-weight:750;cursor:pointer;list-style:none}.contextcontent-section-editor>summary::-webkit-details-marker{display:none}.contextcontent-section-editor>summary::after{content:'›';margin-left:auto;font-size:1.35rem;transform:rotate(90deg)}.contextcontent-section-editor[open]>summary{border-bottom:1px solid var(--chisimba-border,#d7dde5)}.contextcontent-section-editor[open]>summary::after{transform:rotate(-90deg)}.contextcontent-section-editor-body,.contextcontent-manager-card{padding:1.25rem}.contextcontent-manager-card{margin:1rem 0;border:1px solid var(--chisimba-border,#d7dde5);border-radius:12px;background:var(--chisimba-surface,#fff)}.contextcontent-field{display:grid;gap:.35rem;margin-bottom:1rem;font-weight:700}.contextcontent-field input{box-sizing:border-box;width:100%;max-width:45rem}.contextcontent-manage-list{list-style:none;padding:0}.contextcontent-manage-list li,.contextcontent-chapter-placement{display:grid;grid-template-columns:minmax(180px,1fr) auto;gap:.75rem;align-items:center;margin:.6rem 0;padding:.85rem;border:1px solid var(--chisimba-border,#d7dde5);border-radius:10px;background:var(--chisimba-surface-muted,#f8fafb)}.contextcontent-row-actions form{margin:0}.contextcontent-row-type{display:block;color:var(--chisimba-text-muted,#667085);font-size:.75rem;font-weight:750;letter-spacing:.055em;text-transform:uppercase}.contextcontent-placement-form{display:flex;gap:.55rem;align-items:end}.contextcontent-placement-form label{display:grid;gap:.25rem;font-weight:650}.contextcontent-placement-form select{min-width:220px}@media(max-width:760px){.contextcontent-manage-list li,.contextcontent-chapter-placement{grid-template-columns:1fr}.contextcontent-placement-form{align-items:stretch;flex-direction:column}}
</style>
