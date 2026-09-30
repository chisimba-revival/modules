<?php
/** Accessible shared-bank management and explicit question selection. @author Derek Keats */
$this->setLayoutTemplate('banks_layout_tpl.php');
$v=$bankPage;$s=$v['service'];$bank=$v['bank'];
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$t=fn($key)=>$e(ucfirst(html_entity_decode($this->objLanguage->code2Txt('mod_mcqtests_bank_'.$key,'mcqtests'),ENT_QUOTES|ENT_HTML5,'UTF-8')));
$url=fn($params=[])=>$e($this->uri(['action'=>'banks']+$params));
$input=function($name,$value)use($e){return '<input type="hidden" name="'.$e($name).'" value="'.$e($value).'">';};
$form=function($op,$extra=[])use($v,$bank,$url,$input){
    $html='<form class="chisimba-stack" data-bank-operation="'.$op.'" method="post" action="'.$url().'">';
    foreach (['csrf_token'=>$v['token'],'op'=>$op,'bank'=>$bank['id']??'','source'=>$v['source'],'set'=>$v['setid'],'target'=>$v['target']]+$extra as $k=>$value) $html.=$input($k,$value);
    return $html;
};
$button=fn($key)=>'<button type="submit" class="button chisimba-button-primary">'.$this->getObject('iconservice','ui')->render('check',['decorative'=>true]).'<span>'.$t($key).'</span></button>';
$bankSelect=function()use($v,$s,$e,$t){$html='<div class="chisimba-form-field"><label for="bank-destination">'.$t('destination').'</label><select required id="bank-destination" name="bank">';foreach($s->available($v['context'],true) as $b)$html.='<option value="'.$e($b['id']).'"'.(($v['bank']['id']??'')===$b['id']?' selected':'').'>'.$e($b['name']).'</option>';return $html.'</select></div>';};
$plain=static fn($value)=>html_entity_decode(strip_tags((string)$value),ENT_QUOTES|ENT_HTML5,'UTF-8');
echo '<div data-bank-workspace data-bank-id="'.$e($bank['id']??'').'" data-bank-owner="'.$e($this->objUser->userId()).'" data-bank-saved="'.(($v['result']['operation']??'')==='pull'?'1':'0').'" class="chisimba-stack"><section class="chisimba-form-card chisimba-form-card--wide"><h1>'.$t('title').'</h1><p>'.$t('intro').'</p><nav class="chisimba-form-actions chisimba-form-actions--equal"><a class="button chisimba-button-secondary" href="'.$url().'">'.$t('all').'</a>'.$this->getObject('contextualhelp','help')->show('mcqtests','banks',true).'</nav>';
if ($v['error']!=='') echo '<p role="alert">'.$t(substr($v['error'],0,5)==='bank_'?substr($v['error'],5):'invalid').'</p>';
if ($v['result']) {
    echo '<p role="status">'.$t('saved');
    foreach (['added','duplicates','conflicts'] as $key) if (isset($v['result'][$key])) echo ' '.$t($key).': '.(int)$v['result'][$key].'.';
    echo '</p>';
    if (!empty($v['result']['conflicts'])) {
        echo '<p>'.$t('conflict_help').'</p><ul>';
        foreach($v['result']['conflictQuestions']??[] as $stem)echo '<li>'.$e($plain($stem)).'</li>';
        echo '</ul>';
    }
}
echo '</section>';
if ($v['sourceQuestions']) {
    echo '<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$t('add').'</h2><p>'.$t('copy_notice').'</p>';
    echo $form($v['setid']!==''?'addset':'addtest',$v['sourceSet']?['version'=>$v['sourceSet']['version']]:[]).$bankSelect();
    if ($v['setid']!=='') {
        $chapter=$this->getParam('chapter',$v['sourceSet']['title']);
        echo '<div class="chisimba-form-field"><label for="bank-chapter">'.$t('chapter').'</label><input id="bank-chapter" name="chapter" maxlength="200" value="'.$e(is_string($chapter)?$chapter:'').'"></div>';
        foreach ($v['sourceQuestions'] as $q) {
            $index=(string)$q['metadata']['source']['index'];$posted=$this->getParam('selected',[]);
            $checked=($_SERVER['REQUEST_METHOD']??'')!=='POST'||(is_array($posted)&&in_array($index,$posted,true));
            echo '<div class="chisimba-form-field"><label><input type="checkbox" name="selected[]" value="'.$e($index).'"'.($checked?' checked':'').'> '.$e($plain($q['stem'])).'</label></div>';
        }
    } else echo '<p>'.count($v['sourceQuestions']).' '.$t('questions').'</p>';
    echo '<div class="chisimba-form-actions">'.$button($v['setid']!==''?'add_selected':'add_all').'</div></form></section>';
}
if ($bank) {
    echo '<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$e($bank['name']).'</h2>';
    $all=$s->items($bank['id'],$v['context']);$chapters=[];
    foreach ($all as $item) $chapters=array_merge($chapters,$item['content']['chapters']??[]);
    $chapters=array_unique($chapters);natcasesort($chapters);
    echo '<form method="get" action="'.$e($this->uri([])).'">'.$input('module','mcqtests').$input('action','banks').$input('bank',$bank['id']).$input('target',$v['target']).'<div class="chisimba-form-field"><label for="bank-filter">'.$t('chapter').'</label><select id="bank-filter" name="filter"><option value="">'.$t('all_chapters').'</option>';
    foreach ($chapters as $chapter) echo '<option value="'.$e($chapter).'"'.($this->getParam('filter','')===$chapter?' selected':'').'>'.$e($chapter).'</option>';
    echo '</select></div>'.$button('filter').'</form>';
    if (!$v['items']) echo '<p>'.$t('empty').'</p>';
    else {
        echo $form('pull').'<p>'.$t('selection_help').'</p><div class="chisimba-form-actions"><button type="button" class="button chisimba-button-secondary" data-bank-select-all hidden>'.$t('select_all').'</button><button type="button" class="button chisimba-button-secondary" data-bank-clear hidden>'.$t('clear').'</button><span role="status" data-bank-count data-label="'.$t('selected_count').'"></span></div>';
        foreach ($v['items'] as $item) {
            $q=$item['content'];$posted=$this->getParam('selected',[]);
            echo '<article class="chisimba-form-card"><label><input type="checkbox" name="selected[]" value="'.$e($item['id']).'"'.(is_array($posted)&&in_array($item['id'],$posted,true)?' checked':'').'> '.$e($plain($q['stem'])).'</label><details><summary>'.$t('details').'</summary><ol>';
            foreach ($q['answers'] as $answer) echo '<li>'.$e($plain($answer['answer'])).(!empty($answer['correct'])?' — '.$t('correct'):'').'</li>';
            echo '</ol><p>'.$e(implode(', ',$q['chapters']??[])).'</p>';
            // Metadata is only rendered after bank access checks, never by candidate templates.
            $meta=$q['metadata']??[];$candidate=$meta['candidate']??($meta['metadata']['candidate']??[]);
            foreach (['basis'=>$candidate['sourceBasis']??'','rationale'=>$candidate['rationale']??'','source_title'=>$meta['source']['title']??''] as $key=>$value) if (is_string($value)&&$value!=='') echo '<p class="chisimba-preserve-whitespace"><strong>'.$t($key).':</strong> '.$e($value).'</p>';
            echo '</details></article>';
        }
        if(!$s->teaches($v['context']))echo '<p>'.$t('choose_course').'</p>';
        echo '<div class="chisimba-form-field"><label for="bank-target">'.$t('target').'</label><select required id="bank-target" name="target"><option value="">'.$t('choose_test').'</option>';
        foreach ($v['tests'] as $test) if ($test['status']!=='open') echo '<option value="'.$e($test['id']).'"'.($v['target']===$test['id']?' selected':'').'>'.$e($test['name']).'</option>';
        echo '</select></div><div class="chisimba-form-actions">'.$button('pull').'</div></form>';
    }
    if ($v['target']!=='') echo '<p><a class="button chisimba-button-secondary" href="'.$e($this->uri(['action'=>'view','id'=>$v['target']])).'">'.$t('open_test').'</a></p>';
    echo '</section>';
    if ($s->manages($bank)) {
        $names=[];foreach(json_decode($bank['managers_json'],true) as $id){$person=$this->getObject('userservice','security')->findByUserId($id);if($person)$names[]=$person['username'];}
        echo '<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$t('sharing').'</h2><p>'.$t('sharing_help').'</p>'.$form('share',['version'=>$bank['version']]);
        $selectedCourses=array_keys(json_decode($bank['shares_json'],true));
        if($v['error']!==''&&$this->getParam('op')==='share')$selectedCourses=$this->getParam('courses',[]);
        if(!is_array($selectedCourses))$selectedCourses=[];
        echo '<fieldset><legend>'.$t('courses').'</legend>';
        foreach($this->getObject('dbcontext','context')->getListOfContext() as $course) {
            if($course['contextcode']==='root')continue;
            if($v['error']==='' && (json_decode($bank['shares_json'],true)[$course['contextcode']]??null)!==(string)$course['id'])$selectedCourses=array_diff($selectedCourses,[$course['contextcode']]);
            if($course['status']!=='Published'&&!$s->teaches($course['contextcode'])&&!in_array($course['contextcode'],$selectedCourses,true))continue;
            echo '<div class="chisimba-form-field"><label><input type="checkbox" name="courses[]" value="'.$e($course['contextcode']).'"'.(in_array($course['contextcode'],$selectedCourses,true)?' checked':'').'> '.$e($course['title']).'</label></div>';
        }
        echo '</fieldset>';
        $sitewide=isset(json_decode($bank['shares_json'],true)['*']);
        if($this->objUser->isAdmin())echo '<div class="chisimba-form-field"><label><input type="checkbox" name="sitewide" value="1"'.($sitewide?' checked':'').'> '.$t('sitewide').'</label></div>';
        elseif($sitewide)echo $input('sitewide','1').'<p>'.$t('sitewide').'</p>';
        $value=implode("\n",$names);if($v['error']!==''&&$this->getParam('op')==='share')$value=$this->getParam('managers',$value);
        echo '<div class="chisimba-form-field"><label for="bank-managers">'.$t('managers').'</label><textarea id="bank-managers" name="managers" rows="3">'.$e(is_string($value)?$value:'').'</textarea></div>';
        echo '<div class="chisimba-form-actions">'.$button('save_sharing').'</div></form></section>';
    }
}
echo '<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$t('available').'</h2><ul>';
foreach ($v['banks'] as $b) echo '<li><a href="'.$url(['bank'=>$b['id'],'target'=>$v['target']]).'">'.$e($b['name']).'</a></li>';
echo '</ul></section>';
if ($s->author()) echo '<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$t('create').'</h2>'.$form('create').'<div class="chisimba-form-field"><label for="bank-name">'.$t('name').'</label><input id="bank-name" name="name" required maxlength="200" value="'.$e(is_string($this->getParam('name',''))?$this->getParam('name',''):'').'"></div><div class="chisimba-form-actions">'.$button('create').'</div></form></section>';
echo '</div><script src="'.$e($this->getResourceUri('js/banks.js')).'" defer></script>';
