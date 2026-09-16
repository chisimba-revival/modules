<?php
$r=$this->getObject('spokenrenderer'); $e=['spokenrenderer','escape']; $store=$this->getObject('spokenstore');
$activity=$spokenActivity; $attempt=$spokenAttempt;
$course=$this->getObject('dbcontext','context')->getContextDetails($spokenContext);
echo '<section class="chisimba-form-card chisimba-form-card--wide"><header><p>'.$e($r->text('course')).': '.$e($course['title']??$spokenContext).' ('.$e($spokenContext).')</p><h1>'.$e($r->text('title')).'</h1><p>'.$e($r->text('formative')).'</p></header>';
echo '<nav class="chisimba-form-actions">'.$r->link('all_activities','list',['action'=>'list']);
if ($spokenTeacher) echo $r->link('create','plus',['action'=>'edit']);
echo $this->getObject('contextualhelp','help')->show('spokenassessment','practice',true).'</nav>';
if ($spokenAction==='list') {
    $rows=$store->activities($spokenContext,$spokenTeacher,$spokenPage); $more=count($rows)>20;
    if (!$rows) echo '<p>'.$e($r->text('empty')).'</p>';
    echo '<div class="chisimba-publication-card-grid">';
    foreach (array_slice($rows,0,20) as $row) {
        echo '<article class="chisimba-publication-card"><div class="chisimba-publication-card__body"><h2>'.$e($row['title']).'</h2><p>'.$e(mb_substr($row['prompt'],0,220)).'</p><p>'.$e($r->text($row['published']?'published':'draft')).'</p>'.$r->link('open','arrow-right',['action'=>'activity','id'=>$row['id']]).'</div></article>';
    }
    echo '</div><nav class="chisimba-form-actions">';
    if ($spokenPage>1) echo $r->link('previous','arrow-left',['page'=>$spokenPage-1]);
    if ($more) echo $r->link('next','arrow-right',['page'=>$spokenPage+1]);
    echo '</nav>';
} elseif ($spokenAction==='edit') {
    $input=$activity??[];
    echo $r->form('save',$input['id']??'',$spokenToken,$spokenContext).'<input type="hidden" name="version" value="'.(int)($input['version']??0).'">';
    echo '<div class="chisimba-form-field"><label for="spoken_title">'.$e($r->text('activity_title')).'</label><input id="spoken_title" type="text" name="title" required maxlength="200" value="'.$e($input['title']??'').'"></div>';
    echo $r->textarea('prompt','prompt',$input['prompt']??'',10000,true).$r->textarea('outcomes','outcomes',$input['outcomes']??'',10000,true);
    echo '<div class="chisimba-form-field"><label for="spoken_rubric">'.$e($r->text('rubric')).'</label><select name="rubric_id" id="spoken_rubric"><option value="">'.$e($r->text('outcomes_only')).'</option>';
    foreach ($this->getObject('rubricservice','rubric')->listRubrics($spokenContext) as $rubric) echo '<option value="'.$e($rubric['id']).'"'.(($input['rubric_id']??'')===$rubric['id']?' selected':'').'>'.$e($rubric['title']).'</option>';
    echo '</select></div><p><label><input type="checkbox" name="published" value="1"'.(!empty($input['published'])?' checked':'').'> '.$e($r->text('publish')).'</label></p><div class="chisimba-form-actions">'.$r->submit('save','save').'</div></form>';
} elseif ($spokenAction==='activity') {
    echo '<h2 class="chisimba-form-section">'.$e($activity['title']).'</h2><p>'.nl2br($e($activity['prompt'])).'</p><h3>'.$e($r->text('outcomes')).'</h3><p>'.nl2br($e($activity['outcomes'])).'</p>';
    if ($spokenTeacher) echo $r->link('edit','pencil',['action'=>'edit','id'=>$activity['id']]);
    $own=$store->attempts($activity['id'],$spokenUser);
    if ($activity['published'] && count($own)<5) {
        echo '<section class="chisimba-form-section"><h2>'.$e($r->text('your_response')).'</h2><p>'.$e($r->text('audio_help')).'</p>';
        echo $r->form('upload',$activity['id'],$spokenToken,$spokenContext,true).'<input type="hidden" name="upload_token" value="'.bin2hex(random_bytes(16)).'">';
        echo '<div class="chisimba-form-actions"><button type="button" class="button chisimba-button-secondary" data-record disabled>'.$this->getObject('iconservice','ui')->render('mic',['decorative'=>true]).'<span>'.$e($r->text('record')).'</span></button><button type="button" class="button chisimba-button-secondary" data-stop disabled>'.$this->getObject('iconservice','ui')->render('square',['decorative'=>true]).'<span>'.$e($r->text('stop')).'</span></button></div>';
        echo '<p role="status" aria-live="polite" data-record-status data-recording="'.$e($r->text('recording')).'" data-ready="'.$e($r->text('recorded')).'" data-error="'.$e($r->text('record_error')).'" data-uploading="'.$e($r->text('uploading')).'" data-upload-error="'.$e($r->text('upload_retry')).'">'.$e($r->text('upload_fallback')).'</p>';
        echo '<audio data-record-preview controls hidden></audio><p><a class="button chisimba-button-secondary" data-record-download hidden>'.$e($r->text('download_recording')).'</a></p><div class="chisimba-form-field"><label for="spoken_audio">'.$e($r->text('audio_file')).'</label><input id="spoken_audio" type="file" name="audio" accept="audio/*,.webm,.m4a" required></div><p><label class="chisimba-checkbox-field"><input type="checkbox" name="consent" value="1" required> '.$e($r->text('consent')).'</label></p><div class="chisimba-form-actions">'.$r->submit('submit_audio','upload').'</div></form></section>';
        $this->appendArrayVar('headerParams','<script defer src="'.$e($this->getResourceUri('recorder.js','spokenassessment')).'"></script>');
    }
    echo '<h2 class="chisimba-form-section">'.$e($r->text($spokenTeacher?'course_attempts':'your_attempts')).'</h2>';
    $rows=$spokenTeacher?$store->attempts($activity['id'],null,$spokenPage,21):$own; $more=count($rows)>20;
    if (!$rows) echo '<p>'.$e($r->text('no_attempts')).'</p>';
    foreach (array_slice($rows,0,20) as $row) {
        echo '<article class="chisimba-form-section"><p>';
        if ($spokenTeacher) echo $e($this->getObject('user','security')->fullname($row['userid'])).' · ';
        echo $r->date($row['date_created']).' · '.$e($r->text('state_'.$row['state'])).'</p>'.$r->link('view_attempt','headphones',['action'=>'attempt','id'=>$row['id']]).'</article>';
    }
    if ($spokenTeacher) {
        echo '<nav class="chisimba-form-actions">';
        if ($spokenPage>1) echo $r->link('previous','arrow-left',['action'=>'activity','id'=>$activity['id'],'page'=>$spokenPage-1]);
        if ($more) echo $r->link('next','arrow-right',['action'=>'activity','id'=>$activity['id'],'page'=>$spokenPage+1]);
        echo '</nav>';
    }
} elseif ($spokenAction==='attempt') {
    $snapshot=json_decode($attempt['snapshot_json'],true); $owner=$attempt['userid']===$spokenUser;
    echo '<h2>'.$e($snapshot['title']).'</h2><p>'.nl2br($e($snapshot['prompt'])).'</p><p>'.$e($r->text('state_'.$attempt['state'])).'</p>';
    echo '<audio controls preload="metadata" aria-label="'.$e($r->text('your_response')).'" src="'.$e($this->uri(['action'=>'audio','id'=>$attempt['id']],'spokenassessment')).'"></audio>';
    echo '<div class="chisimba-form-actions">'.$r->link('refresh','refresh-cw',['action'=>'attempt','id'=>$attempt['id']]).$r->link('activity','arrow-left',['action'=>'activity','id'=>$attempt['activity_id']]).'</div>';
    if ($attempt['original_transcript']!=='') echo '<details><summary>'.$e($r->text('original')).'</summary><p>'.nl2br($e($attempt['original_transcript'])).'</p></details>';
    if ($owner && in_array($attempt['state'],['transcript_ready','transcription_failed'],true)) {
        echo '<p>'.$e($r->text('transcript_help')).'</p>'.$r->form('approve',$attempt['id'],$spokenToken,$spokenContext).$r->textarea('transcript','transcript',$attempt['original_transcript'],30000,true).$r->submit('approve','check').'</form>';
    }
    if ($attempt['approved_transcript']!=='') echo '<h3>'.$e($r->text('approved')).'</h3><p>'.nl2br($e($attempt['approved_transcript'])).'</p>';
    if ($attempt['error_code']!=='') echo '<p role="status">'.$e($r->text($attempt['error_code'])).'</p>';
    $stale=in_array($attempt['state'],['transcribing','feedback_processing'],true) && strtotime($attempt['date_updated'].' UTC')<time()-900;
    if ($this->getObject('spokenservice')->automaticEnabled() && ($stale || in_array($attempt['state'],['transcription_failed','feedback_failed'],true))) echo $r->form('retry',$attempt['id'],$spokenToken,$spokenContext).'<p>'.$e($r->text('retry_help')).'</p>'.$r->submit('retry','refresh-cw').'</form>';
    $feedback=json_decode($attempt['feedback_json'],true);
    if (is_array($feedback) && isset($feedback['summary'])) {
        echo '<section class="chisimba-form-section"><h3>'.$e($r->text('ai_feedback')).'</h3><p>'.$e($r->text('ai_notice')).'</p><p>'.nl2br($e($feedback['summary'])).'</p>';
        foreach (['strengths','nextSteps'] as $key) { echo '<h4>'.$e($r->text($key)).'</h4><ul>'; foreach ($feedback[$key] as $item) echo '<li>'.$e($item).'</li>'; echo '</ul>'; }
        foreach ($feedback['criteria'] as $item) echo '<h4>'.$e($item['criterion']).'</h4><p>'.$e($item['feedback']).'</p>';
        echo '</section>';
    }
    if ($attempt['teacher_feedback']!=='') echo '<section class="chisimba-form-section"><h3>'.$e($r->text('teacher_feedback')).'</h3><p>'.nl2br($e($attempt['teacher_feedback'])).'</p></section>';
    if ($spokenTeacher) echo $r->form('feedback',$attempt['id'],$spokenToken,$spokenContext).$r->textarea('feedback','teacher_feedback',$attempt['teacher_feedback'],15000,true).$r->submit('save_feedback','save').'</form>';
    if ($owner) echo $r->form('reflect',$attempt['id'],$spokenToken,$spokenContext).$r->textarea('reflection','reflection',$attempt['reflection']).$r->submit('save_reflection','save').'</form>';
    elseif ($attempt['reflection']!=='') echo '<h3>'.$e($r->text('reflection')).'</h3><p>'.nl2br($e($attempt['reflection'])).'</p>';
}
echo '</section>';
