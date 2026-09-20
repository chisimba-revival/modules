<?php
$this->setLayoutTemplate('workspace_layout_tpl.php');
$r=$this->getObject('workshoprenderer');$e=[$r,'escape'];$t=fn($key)=>$e($r->text($key));
$service=$this->getObject('examservice');$card='chisimba-form-card chisimba-form-card--wide';
echo '<div class="chisimba-stack"><section class="'.$card.'"><h1>'.$t('exam_title').'</h1><p>'.$t('exam_intro').'</p><nav class="chisimba-form-actions" style="align-items:stretch;flex-wrap:wrap">'.$r->link('exam_new','plus',['action'=>'examnew']).$r->link('exam_my','list',['action'=>'exams']).($examRow?$r->link('my_sets','layers',['examid'=>$examRow['id']]):'').$this->getObject('contextualhelp','help')->show('mcqgenerator','exams',true).'</nav>';
if($examError!=='')echo '<p role="alert" class="error">'.$t($examError).'</p>';
elseif($this->getParam('saved')==='1')echo '<p role="status">'.$t('exam_saved').'</p>';
echo '</section>';
if(!$examRow){
 echo '<section class="'.$card.'"><details'.($this->getParam('action')==='examnew'||$examError!==''?' open':'').'><summary>'.$t('exam_new').'</summary>'.$r->form('examcreate',$examToken).'<div class="chisimba-form-field"><label for="exam-title">'.$t('exam_name').'</label><input type="text" id="exam-title" name="title" maxlength="200" required value="'.$e($examTitle).'"></div><div class="chisimba-form-actions">'.$r->button('exam_create','plus').'</div></form></details></section>';
 foreach(array_slice($examRows,0,20) as $item){$c=$service->content($item);echo '<article class="'.$card.'"><h2>'.$e($item['title']).'</h2><p>'.(int)$item['chapter_count'].' '.$t('exam_chapter_sets').' · '.count($c['questions']).' '.$t('exam_questions').' · '.$t($c['reviewed']?'reviewed':'draft_notice').'</p><div class="chisimba-form-actions">'.$r->link('exam_workspace_open','layers',['examid'=>$item['id']]).$r->link('exam_assemble','clipboard-list',['action'=>'examview','id'=>$item['id']]).$r->deleteForm($item,$examToken,true).'</div></article>';}
 echo '<nav class="chisimba-form-actions">';if($examPage>1)echo $r->link('previous','arrow-left',['action'=>'exams','page'=>$examPage-1]);if(count($examRows)>20)echo $r->link('next','arrow-right',['action'=>'exams','page'=>$examPage+1]);echo '</nav>';
}else{
 $content=$service->content($examRow);$draft=is_array($examDraft)?$examDraft:null;
 $chapterCounts=array_count_values(array_column($content['questions'],'sourceSetId'));
 echo '<section class="'.$card.'"><h2>'.$e($examRow['title']).'</h2><p>'.count($content['questions']).' '.$t('exam_questions').' · '.$t('exam_total').': '.array_sum(array_column($content['questions'],'marks')).'</p><p>'.$t('exam_snapshot').'</p>';
 if($content['questions'])echo '<div class="chisimba-form-actions">'.$r->link('paper_odt','download',['action'=>'examdownload','id'=>$examRow['id']]).$r->link('exam_download_key','download',['action'=>'examdownload','id'=>$examRow['id'],'answers'=>'1']).'</div><p>'.$t('saved_exports').'</p>';
 echo '<div class="chisimba-form-actions"><a class="button chisimba-button-secondary" href="#exam-editor">'.$t('exam_review_remove').'</a>'.$r->link('new_question_set','plus',['action'=>'new','examid'=>$examRow['id']]).$r->link('my_sets','layers',['examid'=>$examRow['id']]).'</div></section>';
 // Keep the chapter picker separate from the saved exam editor so additions are small requests.
 echo '<section class="'.$card.'" id="chapters"><h2>'.$t('exam_add_chapter').'</h2><p role="status"><strong>'.$t('exam_saved_count').': <span class="chisimba-pill chisimba-pill--success">'.count($content['questions']).'</span></strong></p><p>'.$t('exam_add_explain').'</p>';
 if($examChapter){
  $source=json_decode($examChapter['questions_json'],true)?:[];$existing=[];
  foreach($content['questions'] as $entry)if($entry['sourceSetId']===$examChapter['id'])$existing[$entry['sourceIndex']]=true;
  echo '<h3>'.$e($examChapter['title']).'</h3><p>'.$t(!empty($examChapter['reviewed'])?'reviewed':'draft_notice').'</p>';
  echo str_replace('<form ', '<form data-exam-dirty data-unsaved="'.$t('exam_unsaved').'" ', $r->form('examadd',$examToken,$examRow['id'])).'<input type="hidden" name="page" value="'.$examPage.'"><input type="hidden" name="version" value="'.$e($examRow['version']).'"><input type="hidden" name="setid" value="'.$e($examChapter['id']).'"><input type="hidden" name="setversion" value="'.$e($examChapter['version']).'">';
  echo '<div class="chisimba-sticky-summary" data-exam-add-count data-saved="'.count($content['questions']).'"><p role="status" aria-live="polite">'.$t('exam_saved_count').': <strong>'.count($content['questions']).'</strong> · '.$t('exam_new_selected').': <strong data-selected-count>0</strong> · '.$t('exam_after_add').': <strong data-add-total>'.count($content['questions']).'</strong></p><div class="chisimba-form-actions">'.$r->button('exam_add_selected','plus').'<a class="button chisimba-button-secondary" href="#exam-editor">'.$t('exam_review_remove').'</a></div></div>';
  foreach($source as $i=>$q){
   if(!($q['included']??true))continue;
   echo '<article style="padding:1rem 0;border-bottom:1px solid var(--chisimba-border-color,#ddd)"><label><input type="checkbox" name="selected[]" value="'.$i.'"'.(isset($existing[$i])?' disabled':'').'> <strong>'.($i+1).'. '.$e($q['stem']).'</strong></label>';
   if(isset($existing[$i]))echo '<p>'.$t('exam_already_added').'</p>';
   echo '<ol type="A">';foreach($q['options'] as $option)echo '<li>'.$e($option).'</li>';echo '</ol><details><summary>'.$t('correct').'</summary><p>'.$e(chr(65+(int)$q['correctIndex']).'. '.($q['options'][$q['correctIndex']]??'')).'</p><p>'.$e($q['sourceBasis']).'</p></details></article>';
  }
  if(!$source)echo '<p>'.$t('exam_no_questions').'</p>';
  echo '<div class="chisimba-form-actions">'.$r->button('exam_add_selected','plus').'</div></form>';
 }
 echo '<details data-exam-picker data-load-error="'.$t('exam_page_failed').'"'.(!$examChapter?' open':'').'><summary>'.$t('exam_choose_chapter').'</summary><div class="chisimba-form-actions" style="flex-wrap:wrap">';
 if(!$examSets)echo '<p>'.$t('exam_no_generated').'</p>';
 foreach(array_slice($examSets,0,20) as $set){echo '<a class="button chisimba-button-secondary" href="'.$e($this->uri(['action'=>'examview','id'=>$examRow['id'],'setid'=>$set['id'],'page'=>$examPage],'mcqgenerator')).'#chapters">'.$this->getObject('iconservice','ui')->render('book-open',['decorative'=>true]).'<span>'.$e($set['title']).'</span>'.(isset($chapterCounts[$set['id']])?'<span class="chisimba-pill chisimba-pill--success" aria-label="'.$e($chapterCounts[$set['id']].' '.$r->text('exam_in_exam')).'">'.$chapterCounts[$set['id']].'</span>':'').'</a>';}
 echo '</div><p data-page-status role="status" hidden></p><nav data-exam-pagination class="chisimba-form-actions">';if($examPage>1)echo $r->link('previous','arrow-left',['action'=>'examview','id'=>$examRow['id'],'page'=>$examPage-1]);if(count($examSets)>20)echo $r->link('next','arrow-right',['action'=>'examview','id'=>$examRow['id'],'page'=>$examPage+1]);echo '</nav></details></section>';
 echo '<section class="'.$card.'" id="exam-editor"><h2>'.$t('exam_arrange').'</h2><p>'.$t('exam_edit_explain').'</p>'.str_replace('<form ', '<form data-exam-dirty data-unsaved="'.$t('exam_unsaved').'" ', $r->form('examsave',$examToken,$examRow['id'])).'<input type="hidden" name="page" value="'.$examPage.'"><input type="hidden" name="version" value="'.$e($examRow['version']).'">';
 echo '<div class="chisimba-sticky-summary" data-exam-edit-count><p role="status" aria-live="polite">'.$t('exam_after_save').': <strong data-keep-count>'.count($content['questions']).'</strong> · '.$t('exam_pending_removals').': <strong data-remove-count>0</strong></p><p>'.$t('exam_removal_notice').'</p><div class="chisimba-form-actions">'.$r->button('exam_save','save').'</div></div>';
 echo '<div class="chisimba-form-field"><label for="exam-name">'.$t('exam_name').'</label><input type="text" id="exam-name" name="title" maxlength="200" required value="'.$e($draft!==null?$examTitle:$examRow['title']).'"></div>';
 echo '<div class="chisimba-form-field"><label for="instructions">'.$t('exam_instructions').'</label><textarea id="instructions" name="exam[instructions]" rows="3" maxlength="4000">'.$e($draft['instructions']??$content['instructions']).'</textarea></div>';
 $headings=$draft!==null?($draft['headings']??'')==='1':$content['headings'];
 echo '<p><label><input type="checkbox" name="exam[headings]" value="1"'.($headings?' checked':'').'> '.$t('exam_headings').'</label></p>';
 foreach($content['questions'] as $i=>$entry){
  $key=$entry['id'];$input=$draft['entries'][$key]??null;$q=$entry['question'];
  echo '<fieldset data-exam-entry class="'.$card.'"><legend>'.($i+1).'. '.$e($entry['chapter']).'</legend><p><strong>'.$e($q['stem']).'</strong></p><details><summary>'.$t('exam_preview').'</summary><ol type="A">';foreach($q['options'] as $option)echo '<li>'.$e($option).'</li>';echo '</ol><p>'.$t('correct').': '.$e(chr(65+$q['correctIndex']).'. '.$q['options'][$q['correctIndex']]).'</p></details><div class="chisimba-form-actions" style="align-items:end;flex-wrap:wrap">';
  echo '<label><input type="checkbox" data-exam-keep name="exam[entries]['.$key.'][keep]" value="1"'.($input===null||($input['keep']??'')==='1'?' checked':'').'> '.$t('exam_keep').'</label>';
  echo '<button hidden data-exam-remove type="button" class="button chisimba-button-danger"><span data-remove-label>'.$this->getObject('iconservice','ui')->render('trash-2',['decorative'=>true]).$t('exam_remove_question').'</span><span data-undo-label hidden>'.$this->getObject('iconservice','ui')->render('rotate-ccw',['decorative'=>true]).$t('exam_undo_remove').'</span></button><span data-removal-pending hidden class="chisimba-pill">'.$t('exam_removal_pending').'</span>';
  foreach(['order'=>[$i+1,9999],'marks'=>[$entry['marks'],100]] as $field=>$value)echo '<div class="chisimba-form-field"><label for="'.$field.$key.'">'.$t('exam_'.$field).'</label><input style="width:7rem" type="number" id="'.$field.$key.'" name="exam[entries]['.$key.']['.$field.']" min="1" max="'.$value[1].'" required value="'.$e($input[$field]??$value[0]).'"></div>';
  echo '</div></fieldset>';
 }
 echo '<p><label><input type="checkbox" name="exam[mixAnswers]" value="1"'.(($draft['mixAnswers']??'')==='1'?' checked':'').'> '.$t('exam_mix_answers').'</label></p><p>'.$t('exam_mix_explain').'</p>';
 $reviewed=$draft!==null?($draft['reviewed']??'')==='1':$content['reviewed'];
 echo '<p><label><input type="checkbox" name="exam[reviewed]" value="1"'.($reviewed?' checked':'').'> '.$t('exam_review').'</label></p><div class="chisimba-form-actions">'.$r->button('exam_save','save').'</div><input type="hidden" name="exam[complete]" value="1"></form></section><div class="chisimba-form-actions">'.$r->deleteForm($examRow,$examToken,true).'</div>';
}
echo '</div><script defer src="'.$e($this->getResourceUri('delete.js','mcqgenerator')).'?v=040"></script>';

echo '<script defer src="'.$e($this->getResourceUri('exams.js','mcqgenerator')).'?v=053"></script>';
