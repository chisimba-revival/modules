<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$rights=$this->getObject('grassguiderights','grassguide');
if($grassView==='rightstask') {
    $item=$grassRightsItem;$d=$grassInput??$item['task'];
    echo '<h2>'.$t('rights_task').' — '.$e($item['name']).'</h2><p>'.$t($item['kind']==='media'?'media':$item['field']).' · '.$e($item['licence']?:$r->text('rights_unknown')).' · '.$t($item['classification']).'</p>';
    if($item['stale'])echo '<p role="alert">'.$t('permission_stale').'</p>';
    if($item['source_url']!=='')echo '<p><a href="'.$e($item['source_url']).'" target="_blank" rel="noopener noreferrer">'.$t('open_source').'</a> — '.$e($item['creator']).'</p>';
    if($item['kind']==='media'&&$item['content']['kind']==='image')echo '<img src="'.$e($item['content']['image_url']).'" alt="'.$e($item['content']['alt_text']).'" width="320" referrerpolicy="no-referrer">';
    if($item['kind']==='field')echo '<details><summary>'.$t('current_content').'</summary><p>'.$e(is_array($item['content']['value'])?implode(', ',$item['content']['value']):$item['content']['value']).'</p></details>';
    echo $start('saverights','',$d['revision']??0).$hidden('target',$item['target']).$hidden('fingerprint',$grassInput['fingerprint']??$item['fingerprint']);
    echo '<div class="chisimba-publishing-layout"><fieldset class="chisimba-form-card"><legend>'.$t('replacement_tracking').'</legend>'.$r->select('task_status',$d,['todo','planned','resolved'],false).$r->field('assignee',$d,200).$r->field('due_date',$d,10).$r->field('replacement_url',$d,1500).$r->field('task_notes',$d,4000,false,true).'<p>'.$t('tracking_notice').'</p></fieldset>';
    echo '<fieldset class="chisimba-form-card"><legend>'.$t('publication_decision').'</legend>'.$r->select('decision',$d,['inherit','exclude','commercial_permission'],false).$r->field('permission_evidence',$d,4000,false,true).$r->field('public_credit',$d,1500,false,true).'<p>'.$t('permission_notice').'</p></fieldset></div>';
    echo '<div class="chisimba-form-actions">'.$r->button('save','save').$r->link('rights','arrow-left',['action'=>'rights']).'</div></form>';
} else {
    $inventory=$rights->inventory();$items=$inventory['items'];$policy=$grassInput??$rights->policy();
    $images=array_filter($items,fn($i)=>$i['kind']==='media'&&$i['content']['kind']==='image');
    $nc=array_filter($images,fn($i)=>$i['classification']==='nc');
    $allowed=array_filter($images,fn($i)=>grassguiderights::eligible($i,'commercial'));
    $first=array_unique(array_column(array_filter($nc,fn($i)=>$i['priority']==='priority_1'),'species_id'));
    echo '<h2>'.$t('rights').'</h2><p>'.$t('rights_intro').'</p><p>'.sprintf($t('rights_counts'),count($images),count($nc),count($allowed),count($first)).'</p>';
    $progress=['todo'=>0,'planned'=>0,'resolved'=>0];
    foreach($images as $image)$progress[$image['task']['task_status']]++;
    echo '<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h3>'.$t('replacement_progress').'</h3><div class="chisimba-form-grid">';
    foreach($progress as $status=>$count)echo '<div><strong>'.$t($status).'</strong><p>'.$e($count).'</p></div>';
    echo '</div><p>'.$t('progress_notice').'</p></section>';
    echo '<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h3>'.$t('publication_policy').'</h3>'.$start('savepolicy','',$policy['revision']).$r->select('mode',$policy,['noncommercial','commercial'],false).'<label><input type="checkbox" name="app_updates" value="1"'.(!empty($policy['app_updates'])?' checked':'').'> '.$t('app_updates').'</label><p>'.$t('publication_notice').'</p>'.$r->button('save','save').'</form><div class="chisimba-form-actions">'.$r->link('app_export','download',['action'=>'appexport']).$r->link('commercial_preview','download',['action'=>'commercialpreview']).'</div></section>';
    $query=$this->getParam('q','');$query=is_string($query)?mb_substr(trim($query),0,200):'';
    $filter=$this->getParam('rights_filter','nc');$filter=is_string($filter)&&in_array($filter,['nc','priority_1','planned','resolved','rights_unknown','commercial_ok','all','overdue'],true)?$filter:'nc';
    $matches=array_values(array_filter($items,fn($i)=>($query===''||mb_stripos($i['search'],$query)!==false)&&($filter==='all'||$filter===$i['classification']||($filter==='priority_1'&&$i['priority']==='priority_1')||(in_array($filter,['planned','resolved'],true)&&$i['task']['task_status']===$filter)||($filter==='overdue'&&$i['task']['due_date']!==''&&$i['task']['due_date']<gmdate('Y-m-d')&&$i['task']['task_status']!=='resolved'))));
    usort($matches,fn($a,$b)=>[$a['priority'],$a['name'],$a['target']]<=>[$b['priority'],$b['name'],$b['target']]);
    $page=$this->getParam('page','1');$page=is_scalar($page)&&ctype_digit((string)$page)?max(1,(int)$page):1;$pages=max(1,(int)ceil(count($matches)/30));$page=min($page,$pages);
    echo '<form class="chisimba-form chisimba-form-actions" method="get" action="'.$e($this->uri([],'grassguide')).'">'.$hidden('module','grassguide').$hidden('action','rights').$r->field('q',['q'=>$query],200).$r->select('rights_filter',['rights_filter'=>$filter],['nc','priority_1','planned','resolved','rights_unknown','commercial_ok','overdue','all'],false).$r->button('search','search').'</form>';
    echo '<p>'.sprintf($t('rights_page'),$page,$pages,count($matches)).'</p><div class="chisimba-story-grid">';
    foreach(array_slice($matches,($page-1)*30,30) as $i) {
        echo '<article class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h3><i>'.$e($i['name']).'</i></h3><p>'.$t($i['kind']==='media'?'media':$i['field']).' · '.$e($i['licence']?:$r->text('rights_unknown')).'</p><p>'.$t($i['priority']).' · '.$t($i['task']['task_status']).'</p>';
        if($i['kind']==='media'&&$i['content']['kind']==='image')echo '<figure><img src="'.$e($i['content']['image_url']).'" alt="'.$e($i['content']['alt_text']).'" width="240" loading="lazy" referrerpolicy="no-referrer"></figure>';
        if($i['task']['assignee']!==''||$i['task']['due_date']!=='')echo '<p>'.$e($i['task']['assignee']).' · '.$e($i['task']['due_date']).'</p>';
        if($i['stale'])echo '<p>'.$t('permission_stale').'</p>';
        echo '<p>'.$e($i['creator']).'</p>'.$r->link('rights_task','pencil',['action'=>'rightstask','target'=>$i['target']]).'</article>';
    }
    echo '</div><nav class="chisimba-form-actions" aria-label="'.$t('rights_pages').'">';
    foreach(['previous'=>$page-1,'next'=>$page+1] as $label=>$number)if($number>=1&&$number<=$pages)echo $r->link($label,$label==='previous'?'arrow-left':'arrow-right',['action'=>'rights','q'=>$query,'rights_filter'=>$filter,'page'=>$number]);
    echo '</nav>';
}
