<?php
$this->setLayoutTemplate('workspace_layout_tpl.php');
$r=$this->getObject('workshoprenderer');$e=[$r,'escape'];$t=fn($key)=>$e($r->text($key));
echo '<div class="chisimba-stack"><section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$t('title').'</h1><p>'.$t('intro').'</p><nav class="chisimba-form-actions" style="align-items:stretch;flex-wrap:wrap">'.$r->link('new_question_set','plus',['action'=>'new','examid'=>$workshopExam['id']??'']).$r->link('my_sets','list',['examid'=>$workshopExam['id']??'']).($workshopExam?$r->link('exam_assemble','clipboard-list',['action'=>'examview','id'=>$workshopExam['id']]):'').$r->link('exam_my','list',['action'=>'exams']).$r->link('exam_new','plus',['action'=>'examnew']).$this->getObject('contextualhelp','help')->show('mcqgenerator','guide',true).'</nav>';
if($workshopError!=='')echo '<p role="alert" class="error">'.$t($workshopError).'</p>';
if($workshopExam)echo '<h2>'.$e($workshopExam['title']).'</h2><p>'.$t('exam_workspace_intro').'</p>';
if(!$workshopExam){echo '</section></div>';return;}
if(!$workshopRow){
 $createOpen=$this->getParam('action')==='new'||$workshopError!=='';
 echo '<details id="new-question-set"'.($createOpen?' open':'').'><summary>'.$t('new_set').'</summary>'.$r->form('create',$workshopToken).'<input type="hidden" name="examid" value="'.$e($workshopExam['id']).'">';
 echo '<div class="chisimba-form-field"><label for="set-title">'.$t('set_title').'</label><input id="set-title" name="title" maxlength="200" required value="'.$e($workshopTitle).'"></div>';
 echo '<div class="chisimba-form-field"><label for="question-type">'.$t('question_type').'</label><select id="question-type" name="question_type">';foreach(['mcq','short_answer'] as $kind)echo '<option value="'.$kind.'"'.($this->getParam('question_type','mcq')===$kind?' selected':'').'>'.$t('type_'.$kind).'</option>';echo '</select></div>';
 echo '<div class="chisimba-form-field"><label for="source">'.$t('source').'</label><textarea id="source" name="source" rows="10">'.$e($workshopSource).'</textarea></div>';
 echo '<div class="chisimba-form-field"><label for="sourcefile">'.$t('source_file').'</label><input id="sourcefile" type="file" name="sourcefile" accept=".txt,.odt"><p>'.$t('limits').'</p></div>';
 echo '<div class="chisimba-form-field"><label for="count">'.$t('count').'</label><input id="count" type="number" name="count" min="1" max="30" value="'.$e($workshopCount).'" required><p>'.$t('session_count').'</p></div>';
 echo '<div class="chisimba-form-actions">'.$r->button('save_source','save').'</div></form></details></section><section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$t('my_sets').'</h2>';
 if(!$workshopSets)echo '<p>'.$t('exam_no_chapters').'</p>';
 foreach(array_slice($workshopSets,0,20) as $set)echo '<article class="chisimba-form-card chisimba-form-card--wide"><h3>'.$e($set['title']).'</h3><p>'.$t(($set['question_type']??'mcq')==='short_answer'?'type_short_answer':'type_mcq').'</p><p>'.$t(!empty($set['reviewed'])?'reviewed':'draft_notice').'</p>'.'<div class="chisimba-form-actions">'.$r->link('open','eye',['action'=>'view','id'=>$set['id']]).$r->deleteForm($set,$workshopToken).'</div></article>';
 echo '<nav class="chisimba-form-actions">';if($workshopPage>1)echo $r->link('previous','arrow-left',['page'=>$workshopPage-1,'examid'=>$workshopExam['id']]);if(count($workshopSets)>20)echo $r->link('next','arrow-right',['page'=>$workshopPage+1,'examid'=>$workshopExam['id']]);echo '</nav>';
}else{
 $set=$workshopRow;$short=($set['question_type']??'mcq')==='short_answer';$questions=json_decode($set['questions_json'],true)?:[];
 echo '</section><section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$e($set['title']).'</h2><p>'.$t($short?'type_short_answer':'type_mcq').'</p><details><summary>'.$t('saved_source').'</summary><p class="chisimba-preserve-whitespace">'.nl2br($e($set['source_text'])).'</p></details></section>';
 if(!$short)echo '<section class="chisimba-form-card chisimba-form-card--wide"><details'.($this->getParam('action')==='derive'?' open':'').'><summary>'.$t('derive_short').'</summary><p>'.$t('derive_explain').'</p>'.$r->form('derive',$workshopToken,$set['id']).'<input type="hidden" name="version" value="'.$e($set['version']).'"><div class="chisimba-form-field"><label for="derive-title">'.$t('set_title').'</label><input id="derive-title" name="title" required maxlength="200" value="'.$e($this->getParam('action')==='derive'?$workshopTitle:mb_substr($set['title'],0,160).' — '.$r->text('type_short_answer')).'"></div><div class="chisimba-form-field"><label for="derive-count">'.$t('count').'</label><input id="derive-count" name="count" type="number" min="1" max="30" required value="'.$e($workshopCount).'"></div><div class="chisimba-form-actions">'.$r->button('derive_short','plus').'</div></form></details></section>';
 $issues=json_decode($set['validation_json']??'[]',true)?:[];
 if($issues){
  echo '<section class="chisimba-form-card chisimba-form-card--wide"><h3>'.$t('validation_title').'</h3><p>'.$t('validation_intro').'</p><ul>';
  foreach($issues as $issue){
   $code=$issue['code']??'';
   if(!in_array($code,['count','format','duplicates','quote','partial','additional_shortfall','additional_timeout'],true))continue;
   $message=$r->text('validation_'.$code);
   foreach(['question','expected','actual'] as $key)$message=str_replace('{'.$key.'}',(string)(int)($issue[$key]??0),$message);
   echo '<li>'.$e($message);
   if($code==='quote')echo '<blockquote>'.$e($issue['excerpt']??'').'</blockquote>';
   echo '</li>';
  }
  echo '</ul><p>'.$t('validation_next').'</p></section>';
 }
 if(!$questions||in_array($set['state'],['generating','processing'],true)){
  $failedJob=json_decode($set['generation_json']??'[]',true);
  if($set['state']==='failed'&&($failedJob['lastError']??'')==='generation_timeout'&&$workshopError!=='generation_timeout')echo '<p class="error" role="alert">'.$t('generation_timeout').'</p>';
  echo '<section class="chisimba-form-card chisimba-form-card--wide">';
  if(in_array($set['state'],['generating','processing'],true)){
   $job=json_decode($set['generation_json']??'',true);
   if($job&&$set['state']==='generating'){
    echo '<p role="status">'.$e(str_replace(['{done}','{total}'],[(string)$job['next'],(string)count($job['parts'])],$r->text('section_progress'))).'</p>';
    echo str_replace('<form ', '<form data-auto-section ', $r->form('generatepart',$workshopToken,$set['id'])).'<div class="chisimba-form-actions">'.$r->button('continue_sections','sparkles').'</div></form>';
   }else echo '<p role="status">'.$t('generation_busy').'</p>';
  }
  else echo $r->form('generate',$workshopToken,$set['id']).'<div class="chisimba-form-field"><label for="generation-count">'.$t('count').'</label><input id="generation-count" type="number" name="count" min="1" max="30" required value="'.$e($set['question_count']).'"></div><p>'.$t($short?'short_generation_notice':'generation_notice').'</p><label><input type="checkbox" name="consent" value="1" required> '.$t('consent').'</label><div class="chisimba-form-actions">'.$r->button('generate','sparkles').'</div></form>';
  echo '</section>';
 }else{
  $downloads='<section class="chisimba-form-card chisimba-form-card--wide"><h3>'.$t('downloads').'</h3><p>'.$t('saved_exports').'</p><div class="chisimba-form-actions">';
  foreach(['odt','txt'] as $format)foreach([0,1] as $answers)$downloads.=$r->link(($answers?'key_':'paper_').$format,'download',['action'=>'download','id'=>$set['id'],'format'=>$format,'answers'=>(string)$answers]);
  $downloads.='</div></section>';
  echo $downloads;
  if(count($questions)<30)echo '<section class="chisimba-form-card chisimba-form-card--wide"><details><summary>'.$t('more_title').'</summary><p>'.$t('more_explain').'</p>'.$r->form('more',$workshopToken,$set['id']).'<input type="hidden" name="version" value="'.$e($set['version']).'"><div class="chisimba-form-field"><label for="additional">'.$t('additional_count').'</label><input type="number" id="additional" name="additional" min="1" max="'.(30-count($questions)).'" value="'.min(5,30-count($questions)).'" required></div><label><input type="checkbox" name="consent" value="1" required> '.$t('consent').'</label><div class="chisimba-form-actions">'.$r->button('more','sparkles').'</div></form></details></section>';
  echo '<section class="chisimba-form-card chisimba-form-card--wide">';
  $context=(string)$this->getObject('dbcontext','context')->getContextCode();
  $course=$this->getObject('dbcontext','context')->getContextDetails($context);
  if($short)echo '<p>'.$t('short_import_unavailable').'</p>';
  elseif(!empty($set['imported_testid']))echo '<p>'.$t('imported').' '.$e($set['imported_context']).'</p>';
  elseif($course && $context!=='' && $context!=='root')echo '<details><summary>'.$t('import_course').'</summary><p>'.$e($course['title']??$context).' — '.$e($context).'</p><p>'.$t('import_explain').'</p>'.$r->form('import',$workshopToken,$set['id']).'<input type="hidden" name="contextcode" value="'.$e($context).'"><div class="chisimba-form-actions">'.$r->button('import_course','download').'</div></form></details>';
  else echo '<p>'.$t('select_course').'</p>';
  echo '</section>'.str_replace('<form ', '<form style="display:grid;gap:var(--chisimba-layout-gap,1.5rem)" ', $r->form('save',$workshopToken,$set['id'])).'<input type="hidden" name="version" value="'.$e($set['version']).'">';
  echo '<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$t('review_heading').'</h2><p>'.$t('review_notice').'</p><div class="chisimba-form-field"><label for="set-title">'.$t('set_title').'</label><input id="set-title" name="title" required maxlength="200" value="'.$e($this->getParam('action')==='derive'?$set['title']:($workshopTitle?:$set['title'])).'"></div></section>';
  if(is_array($workshopDraft)&&count($workshopDraft)===count($questions))$questions=$workshopDraft;
  foreach($questions as $i=>$q){
   echo '<fieldset data-question-review class="chisimba-form-card chisimba-form-card--wide"><legend>'.$t('question').' '.($i+1).'</legend><label><input type="checkbox" data-question-include name="questions['.$i.'][included]" value="1"'.(($q['included']??true)?' checked':'').'> '.$t('include_question').'</label>';
   foreach($issues as $issue){if((int)($issue['question']??0)!==$i+1)continue;$code=$issue['code']??'';if(in_array($code,['format','duplicates','quote'],true))echo '<p class="chisimba-notice chisimba-notice--warning">'.$e(str_replace('{question}',(string)($i+1),$r->text('validation_'.$code))).'</p>';}
   echo '<div class="chisimba-form-field"><label for="q'.$i.'">'.$t('stem').'</label><textarea id="q'.$i.'" name="questions['.$i.'][stem]" required maxlength="4000">'.$e(is_string($q['stem']??null)?$q['stem']:'').'</textarea></div>';
   if($short){
    foreach(['modelAnswer'=>'model_answer','markingPoints'=>'marking_points'] as $field=>$label)echo '<div class="chisimba-form-field"><label for="'.$field.$i.'">'.$t($label).'</label><textarea id="'.$field.$i.'" name="questions['.$i.']['.$field.']" rows="3" required maxlength="4000">'.$e(is_string($q[$field]??null)?$q[$field]:'').'</textarea></div>';
    echo '<p>'.$t('short_answer_length').'</p><div class="chisimba-form-field"><label for="marks'.$i.'">'.$t('suggested_marks').'</label><input id="marks'.$i.'" type="number" name="questions['.$i.'][marks]" min="1" max="10" required value="'.$e($q['marks']??1).'"></div>';
   }else{
   for($j=0;$j<4;$j++)echo '<div class="chisimba-form-field"><label for="q'.$i.'o'.$j.'">'.$t('option').' '.chr(65+$j).'</label><textarea id="q'.$i.'o'.$j.'" name="questions['.$i.'][options][]" rows="2" style="min-height:0;height:auto" required maxlength="4000">'.$e(is_string($q['options'][$j]??null)?$q['options'][$j]:'').'</textarea></div>';
   echo '<div class="chisimba-form-field"><label for="correct'.$i.'">'.$t('correct').'</label><select id="correct'.$i.'" name="questions['.$i.'][correctIndex]">';
   for($j=0;$j<4;$j++)echo '<option value="'.$j.'"'.((is_scalar($q['correctIndex']??null)?(string)$q['correctIndex']:'')===(string)$j?' selected':'').'>'.chr(65+$j).'</option>';
   echo '</select></div>';
   }
   echo '<div class="chisimba-form-field"><label for="basis'.$i.'">'.$t('source_basis').'</label><textarea id="basis'.$i.'" name="questions['.$i.'][sourceBasis]" maxlength="4000" required>'.$e(is_string($q['sourceBasis']??null)?$q['sourceBasis']:'').'</textarea></div></fieldset>';
  }
  echo '<section class="chisimba-form-card chisimba-form-card--wide"><label><input type="checkbox" name="reviewed" value="1"'.(!empty($set['reviewed'])?' checked':'').'> '.$t('review_confirm').'</label><div class="chisimba-form-actions">'.$r->button('save_review','save').'</div></section></form>'.$downloads;
 }
}
if($workshopRow)echo '<div class="chisimba-form-actions">'.$r->deleteForm($workshopRow,$workshopToken).'</div>';
if(!$workshopRow)echo '</section>';
echo '</div>';
echo '<script defer src="'.$e($this->getResourceUri('delete.js','mcqgenerator')).'?v=041"></script>';
