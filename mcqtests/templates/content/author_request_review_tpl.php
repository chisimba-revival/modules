<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$lang=$this->getObject('language','language');
echo '<section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$e($lang->languageText('mod_mcqtests_review_request','mcqtests')).'</h1><p>'.$e($lang->languageText('mod_mcqtests_review_request_help','mcqtests')).'</p>';
if (!empty($authorTargetLabel)) echo '<p><strong>'.$e($authorTargetLabel).'</strong></p>';
echo $this->getObject('contextualhelp','help')->show('mcqtests','authoring');
echo '<form method="post" action="'.$e($this->uri(array('action'=>$authorAction),'mcqtests')).'">'.$this->getObject('authoringrequest','mcqtests')->input();
$labels=array('mark'=>'mod_mcqtests_mark','qMark'=>'mod_mcqtests_mark','name'=>'word_name','title'=>'word_title','question'=>'mod_mcqtests_question','freeformquestion'=>'mod_mcqtests_question','description'=>'mod_mcqtests_description','hint'=>'mod_mcqtests_hint','correctans'=>'mod_mcqtests_selectcorrect','qNum'=>'mod_mcqtests_answer_count');
foreach($authorDraft as $key=>$value) {
    if (preg_match('/^(answer|comment)[0-9]+$/',$key) || isset($labels[$key])) {
        $label=$labels[$key]??(preg_match('/^answer/',$key)?'mod_mcqtests_answer':'mod_mcqtests_comment');
        echo '<div class="chisimba-form-field"><label>'.$e($lang->languageText($label,str_starts_with($label,'mod_')?'mcqtests':'system')).'<textarea name="'.$e($key).'" rows="3">'.$e($value).'</textarea></label></div>';
    } else echo '<input type="hidden" name="'.$e($key).'" value="'.$e($value).'">';
}
echo '<div class="chisimba-form-actions"><button class="button" type="submit">'.$e($lang->languageText('word_confirm','system')).'</button><a class="button chisimba-button-secondary" href="'.$e($this->uri(array('action'=>'newhome'),'mcqtests')).'">'.$e($lang->languageText('word_cancel','system')).'</a></div></form></section>';
