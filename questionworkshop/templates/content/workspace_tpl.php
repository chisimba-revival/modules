<?php
$r=$this->getObject('workshoprenderer');$e=[$r,'escape'];$t=fn($key)=>$e($r->text($key));
echo '<section class="chisimba-form-card"><h1>'.$t('title').'</h1><p>'.$t('intro').'</p><nav class="chisimba-form-actions">'.$r->link('my_sets','list',[]).$this->getObject('contextualhelp','help')->show('questionworkshop','guide',true).'</nav>';
if($workshopError!=='')echo '<p role="alert" class="error">'.$t($workshopError).'</p>';
if(!$workshopRow){
 echo '<h2>'.$t('new_set').'</h2>'.$r->form('create',$workshopToken);
 echo '<div class="chisimba-form-field"><label for="set-title">'.$t('set_title').'</label><input id="set-title" name="title" maxlength="200" required value="'.$e($workshopTitle).'"></div>';
 echo '<div class="chisimba-form-field"><label for="source">'.$t('source').'</label><textarea id="source" name="source" rows="10" maxlength="40000">'.$e($workshopSource).'</textarea></div>';
 echo '<div class="chisimba-form-field"><label for="sourcefile">'.$t('source_file').'</label><input id="sourcefile" type="file" name="sourcefile" accept=".txt,.odt"><p>'.$t('limits').'</p></div>';
 echo '<div class="chisimba-form-field"><label for="count">'.$t('count').'</label><input id="count" type="number" name="count" min="1" max="30" value="'.$e($workshopCount).'" required></div>';
 echo '<div class="chisimba-form-actions">'.$r->button('save_source','save').'</div></form></section><section class="chisimba-form-card"><h2>'.$t('my_sets').'</h2>';
 foreach(array_slice($workshopSets,0,20) as $set)echo '<article class="chisimba-form-card"><h3>'.$e($set['title']).'</h3><p>'.$t(!empty($set['reviewed'])?'reviewed':'draft_notice').'</p>'.'<div class="chisimba-form-actions">'.$r->link('open','eye',['action'=>'view','id'=>$set['id']]).$r->deleteForm($set,$workshopToken).'</div></article>';
 echo '<nav class="chisimba-form-actions">';if($workshopPage>1)echo $r->link('previous','arrow-left',['page'=>$workshopPage-1]);if(count($workshopSets)>20)echo $r->link('next','arrow-right',['page'=>$workshopPage+1]);echo '</nav>';
}else{
 $set=$workshopRow;$questions=json_decode($set['questions_json'],true)?:[];
 echo '<h2>'.$e($set['title']).'</h2><details><summary>'.$t('saved_source').'</summary><p class="chisimba-preserve-whitespace">'.nl2br($e($set['source_text'])).'</p></details>';
 $issues=json_decode($set['validation_json']??'[]',true)?:[];
 if($issues){
  echo '<section class="chisimba-form-card"><h3>'.$t('validation_title').'</h3><p>'.$t('validation_intro').'</p><ul>';
  foreach($issues as $issue){
   $code=$issue['code']??'';
   if(!in_array($code,['count','format','duplicates','quote'],true))continue;
   $message=$r->text('validation_'.$code);
   foreach(['question','expected','actual'] as $key)$message=str_replace('{'.$key.'}',(string)(int)($issue[$key]??0),$message);
   echo '<li>'.$e($message);
   if($code==='quote')echo '<blockquote>'.$e($issue['excerpt']??'').'</blockquote>';
   echo '</li>';
  }
  echo '</ul><p>'.$t('validation_next').'</p></section>';
 }
 if(!$questions){
  if($set['state']==='generating')echo '<p role="status">'.$t('generation_busy').'</p>';
  else echo $r->form('generate',$workshopToken,$set['id']).'<p>'.$t('generation_notice').'</p><label><input type="checkbox" name="consent" value="1" required> '.$t('consent').'</label><div class="chisimba-form-actions">'.$r->button('generate','sparkles').'</div></form>';
 }else{
  echo '<p>'.$t('review_notice').'</p><div class="chisimba-form-actions">';
  foreach(['txt','odt'] as $format)foreach([0,1] as $answers)echo $r->link(($answers?'key_':'paper_').$format,'download',['action'=>'download','id'=>$set['id'],'format'=>$format,'answers'=>(string)$answers]);
  echo '</div>';
  $context=(string)$this->getObject('dbcontext','context')->getContextCode();
  $course=$this->getObject('dbcontext','context')->getContextDetails($context);
  if(!empty($set['imported_testid']))echo '<p>'.$t('imported').' '.$e($set['imported_context']).'</p>';
  elseif($course && $context!=='' && $context!=='root')echo '<details><summary>'.$t('import_course').'</summary><p>'.$e($course['title']??$context).' — '.$e($context).'</p><p>'.$t('import_explain').'</p>'.$r->form('import',$workshopToken,$set['id']).'<input type="hidden" name="contextcode" value="'.$e($context).'"><div class="chisimba-form-actions">'.$r->button('import_course','download').'</div></form></details>';
  else echo '<p>'.$t('select_course').'</p>';
  echo '<p>'.$t('saved_exports').'</p>'.$r->form('save',$workshopToken,$set['id']).'<input type="hidden" name="version" value="'.$e($set['version']).'">';
  echo '<div class="chisimba-form-field"><label for="set-title">'.$t('set_title').'</label><input id="set-title" name="title" required maxlength="200" value="'.$e($workshopTitle?:$set['title']).'"></div>';
  if(is_array($workshopDraft)&&count($workshopDraft)===(int)$set['question_count'])$questions=$workshopDraft;
  foreach($questions as $i=>$q){
   echo '<fieldset class="chisimba-form-card"><legend>'.$t('question').' '.($i+1).'</legend><div class="chisimba-form-field"><label for="q'.$i.'">'.$t('stem').'</label><textarea id="q'.$i.'" name="questions['.$i.'][stem]" required maxlength="4000">'.$e(is_string($q['stem']??null)?$q['stem']:'').'</textarea></div>';
   for($j=0;$j<4;$j++)echo '<div class="chisimba-form-field"><label for="q'.$i.'o'.$j.'">'.$t('option').' '.chr(65+$j).'</label><input id="q'.$i.'o'.$j.'" name="questions['.$i.'][options][]" required maxlength="4000" value="'.$e(is_string($q['options'][$j]??null)?$q['options'][$j]:'').'"></div>';
   echo '<div class="chisimba-form-field"><label for="correct'.$i.'">'.$t('correct').'</label><select id="correct'.$i.'" name="questions['.$i.'][correctIndex]">';
   for($j=0;$j<4;$j++)echo '<option value="'.$j.'"'.((is_scalar($q['correctIndex']??null)?(string)$q['correctIndex']:'')===(string)$j?' selected':'').'>'.chr(65+$j).'</option>';
   echo '</select></div><div class="chisimba-form-field"><label for="basis'.$i.'">'.$t('source_basis').'</label><textarea id="basis'.$i.'" name="questions['.$i.'][sourceBasis]" maxlength="4000" required>'.$e(is_string($q['sourceBasis']??null)?$q['sourceBasis']:'').'</textarea></div></fieldset>';
  }
  echo '<label><input type="checkbox" name="reviewed" value="1"'.(!empty($set['reviewed'])?' checked':'').'> '.$t('review_confirm').'</label><div class="chisimba-form-actions">'.$r->button('save_review','save').'</div></form>';
 }
}
if($workshopRow)echo '<div class="chisimba-form-actions">'.$r->deleteForm($workshopRow,$workshopToken).'</div>';
echo '</section>';
echo '<script defer src="'.$e($this->getResourceUri('delete.js','questionworkshop')).'?v=015"></script>';
