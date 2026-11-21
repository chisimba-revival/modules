<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$r=$this->getObject('grassguiderenderer','grassguide'); $s=$this->getObject('grassguideservice','grassguide');
$e=[$r,'escape']; $t=fn($key)=>$e($r->text($key));
$d=$grassInput ?? $grassRecord ?? [];
$hidden=fn($key,$value)=>'<input type="hidden" name="'.$e($key).'" value="'.$e($value).'">';
$start=function($action,$id,$revision) use($hidden,$grassCsrf,$e) {
    return '<form class="chisimba-form chisimba-flow" method="post" data-grass-form action="'.$e($this->uri(['action'=>$action],'grassguide')).'">'.$hidden('id',$id).$hidden('revision',$revision).$hidden('csrf_token',$grassCsrf).'<p data-save-status role="status" aria-live="polite"></p>';
};
echo '<main class="chisimba-flow"><header><h1>'.$t('title').'</h1><p>'.$t('intro').'</p><nav class="chisimba-form-actions chisimba-form-actions--equal" aria-label="'.$t('navigation').'">';
echo $r->link('catalogue','list',['action'=>'index']).$r->link('setup','settings',['action'=>'setup']).$r->link('rights','shield',['action'=>'rights']).$r->link('new_species','plus',['action'=>'edit']);
echo $this->getObject('contextualhelp','help')->show('grassguide','administration',true).'</nav></header>';
if ($grassError!=='') echo '<p role="alert">'.$t($grassError).'</p>';
if ($grassSaved) echo '<p role="status" data-grass-notice>'.$t('saved').'</p>';
if(in_array($grassView,['rights','rightstask'],true)) { include __DIR__.'/rights_tpl.php';
} elseif ($grassView==='index') {
    $all=$s->all(); $query=$this->getParam('q',''); $query=is_string($query)?mb_substr(trim($query),0,200):'';
    $filter=$this->getParam('state',''); $filter=is_string($filter)?$filter:'';
    $rows=array_filter($all,fn($row)=>($filter==='' || $row['state']===$filter) && ($query==='' || mb_stripos(implode(' ',[$row['scientific_name'],$row['common_name'],$row['other_names'],$row['synonyms']]),$query)!==false));
    echo '<form class="chisimba-form chisimba-form-actions" method="get" action="'.$e($this->uri([],'grassguide')).'">'.$hidden('module','grassguide').$r->field('q',['q'=>$query],200).$r->select('state',['state'=>$filter],['draft','reviewed','archived'],true,false,'all_states').$r->button('search','search').'</form>';
    echo '<p>'.sprintf($t('count'),count($rows),count($all)).'</p>';
    if (!$all) echo '<p>'.$t('empty').'</p>';
    echo '<div class="chisimba-story-grid">';
    foreach ($rows as $row) {
        echo '<article class="chisimba-form-card chisimba-flow"><h2><i>'.$e($row['scientific_name']).'</i></h2><p>'.$e($row['common_name']).'</p><p>'.$t($row['state']).' · '.$e($row['ecological_status']).'</p><div class="chisimba-cluster">'.$r->link('edit','pencil',['action'=>'edit','id'=>$row['id']]).'</div></article>';
    }
    echo '</div>';
} elseif ($grassView==='setup') {
    $preview=$s->seedPreview(); $all=$s->all();
    $pending=count(array_filter($all,fn($row)=>$row['taxonomy_review']!=='reviewed'));
    $traits=count(array_filter($all,fn($row)=>empty($row['heads'])));
    $reviewed=count(array_filter($all,fn($row)=>$row['state']==='reviewed'));
    echo '<section class="chisimba-form-card"><h2>'.$t('setup').'</h2><p>'.sprintf($t('seed_summary'),$preview['total'],count($preview['missing'])).'</p><p>'.$t('seed_notice').'</p>';
    echo '<details><summary>'.$t('preview_names').'</summary><ul>'; foreach ($preview['missing'] as $name) echo '<li>'.$e($name).'</li>'; echo '</ul></details>';
    echo $start('import','',0).$hidden('seed_hash',$preview['sha256']).$r->button('import','download').'</form></section>';
    echo '<section class="chisimba-form-card chisimba-flow"><h2>'.$t('enrichment').'</h2><p>'.$t('enrichment_help').'</p>';
    echo $start('enrich','',0).$r->button('enrich','download').'</form>';
    echo '<p>'.sprintf($t('source_coverage'),...array_map(fn($key)=>count(array_filter($all,fn($row)=>!empty($row[$key]))),['description','diagnostic','habitat','powo_id'])).'</p>';
    echo '<table><caption>'.$t('enrichment_progress').'</caption><thead><tr><th>'.$t('provider').'</th><th>'.$t('state').'</th><th>'.$t('jobs').'</th></tr></thead><tbody>';
    foreach($s->enrichmentJobs() as $job) echo '<tr><td>'.$e($job['provider']).'</td><td>'.$t('job_'.$job['status']).'</td><td>'.$e($job['total']).'</td></tr>';
    echo '</tbody></table>';
    foreach($s->enrichmentErrors() as $problem)echo '<p role="status">'.$e($problem['provider']).': '.$e($problem['last_error']).' ('.$e($problem['total']).') — '.$t('next_attempt').' '.$e($problem['next_attempt']).' UTC</p>';
    echo $r->link('refresh_status','refresh-cw',['action'=>'setup']).'</section>';
    echo '<section class="chisimba-form-card"><h2>'.$t('readiness').'</h2><ul><li>'.sprintf($t('pending_taxonomy'),$pending).'</li><li>'.sprintf($t('missing_traits'),$traits).'</li><li>'.sprintf($t('reviewed_count'),$reviewed).'</li></ul><p>'.$t('setup_scope').'</p>'.$r->link('export','download',['action'=>'export']).'<p>'.$t('export_notice').'</p></section>';
} elseif ($grassView==='edit') {
    $d=array_merge(['state'=>'draft','taxonomy_review'=>'pending','ecology_review'=>'pending','heads'=>[]],$d);
    if($grassRecord) {
        echo '<details><summary>'.$t('enrichment_progress').'</summary><ul>';
        foreach($s->enrichmentJobs($grassId) as $job)echo '<li>'.$e($job['provider']).' — '.$t('job_'.$job['status']).' · '.$e($job['updated_at']).' UTC'.($job['last_error']!==''?' · '.$e($job['last_error']):'').'</li>';
        echo '</ul><p>'.$t('enrichment_help').'</p></details>';
    }
    echo '<h2>'.$t($grassRecord?'edit':'new_species').'</h2>'.$start('save',$grassId,$d['revision'] ?? 0);
    echo '<div class="chisimba-publishing-layout"><div class="chisimba-flow"><fieldset class="chisimba-form-card"><legend>'.$t('identity').'</legend><div class="chisimba-form-grid">';
    foreach (['genus'=>100,'epithet'=>100,'authority'=>250,'common_name'=>200] as $key=>$max) echo $r->field($key,$d,$max,$key!=='authority');
    echo '</div>'.$r->field('other_names',$d,1500,false,true).$r->field('synonyms',$d,3000,false,true).'<p>'.$t('names_help').'</p></fieldset>';
    echo '<fieldset class="chisimba-form-card"><legend>'.$t('identification').'</legend>';
    foreach (['diagnostic'=>12000,'description'=>12000,'similar'=>6000,'habitat'=>6000] as $key=>$max) echo $r->field($key,$d,$max,false,true);
    echo '<fieldset><legend>'.$t('heads').'</legend>';
    foreach (grassguideinput::HEADS as $value) echo '<label class="chisimba-choice"><input type="checkbox" name="heads[]" value="'.$value.'"'.(is_array($d['heads'] ?? null) && in_array($value,$d['heads'],true)?' checked':'').'> '.$t($value).'</label>';
    echo '</fieldset>'.$r->select('awns',$d,['present','absent','variable']).$r->field('trait_source',$d,3000,false,true).'<p>'.$t('traits_help').'</p></fieldset>';
    echo '<fieldset class="chisimba-form-card"><legend>'.$t('ecology').'</legend><div class="chisimba-form-grid">'.$r->select('ecological_status',$d,grassguideinput::ECOLOGY,true,true).$r->select('life_cycle',$d,grassguideinput::LIFE,true,true).$r->select('grazing_value',$d,grassguideinput::GRAZING,true,true).$r->field('succession',$d,120).'</div>'.$r->field('ecology_source',$d,3000,false,true).$r->select('ecology_review',$d,['pending','reviewed'],false).'<p>'.$t('ecology_help').'</p></fieldset>';
    echo '<fieldset class="chisimba-form-card"><legend>'.$t('taxonomy').'</legend>'.$r->field('powo_id',$d,200).$r->field('taxonomy_checked',$d,10).$r->field('taxonomy_source',$d,3000,false,true).$r->select('taxonomy_review',$d,['reported','pending','reviewed'],false).'<p>'.$t('taxonomy_help').'</p></fieldset></div>';
    echo '<aside class="chisimba-form-card chisimba-flow"><h2>'.$t('review').'</h2>'.$r->select('state',$d,['draft','reviewed','archived'],false).$r->field('editor_notes',$d,6000,false,true).'<p>'.$t('review_help').'</p><div class="chisimba-form-actions">'.$r->button('save','save').'</div></aside></div></form>';
    if ($grassRecord) {
        echo '<section class="chisimba-form-card chisimba-flow"><h2>'.$t('media').'</h2>'.$r->link('add_media','plus',['action'=>'media','species_id'=>$grassId]).'<p>'.$t('media_intro').'</p><div class="chisimba-story-grid">';
        foreach ($s->mediaFor($grassId) as $media) echo '<article class="chisimba-form-card">'.($media['kind']==='image'?'<img src="'.$e($media['image_url']).'" alt="'.$e($media['alt_text']).'" width="320" loading="lazy" referrerpolicy="no-referrer">':'').'<h3>'.$t($media['role']).'</h3><p>'.$e($media['caption']).'</p><p>'.$e($media['creator']).' · '.$e($media['licence']).' · '.$t($media['state']).'</p>'.$r->link('edit','pencil',['action'=>'media','id'=>$media['id']]).$r->link('rights','shield',['action'=>'rightstask','target'=>'media:'.$media['id']]).'</article>';
        echo '</div></section><details><summary>'.$t('history').'</summary><ul>';
        foreach ($s->history($grassId) as $event) echo '<li>'.$e($event['created_at']).' UTC · '.$t($event['action']).' · '.$e($event['actor']).'</li>';
        echo '</ul></details>';
    } else echo '<p>'.$t('save_before_media').'</p>';
} elseif ($grassView==='media') {
    $d=array_merge(['kind'=>'image','role'=>'inflorescence','state'=>'active','licence'=>''],$d);
    $parent=$s->one($grassSpecies);
    echo '<h2>'.$t('media').' — '.$e($parent['scientific_name']).'</h2>'.$r->link('back_species','arrow-left',['action'=>'edit','id'=>$grassSpecies]);
    echo $start('savemedia',$grassId,$d['revision'] ?? 0).$hidden('species_id',$grassSpecies).'<fieldset class="chisimba-form-card"><legend>'.$t('media_details').'</legend><p>'.$t('media_intro').'</p>';
    echo '<div class="chisimba-form-grid">'.$r->select('kind',$d,['image','youtube'],false).$r->select('role',$d,grassguideinput::ROLES,false).'</div>';
    foreach (['source_url'=>1500,'image_url'=>1500,'youtube_id'=>11,'caption'=>1000,'alt_text'=>1000,'creator'=>300] as $key=>$max) echo $r->field($key,$d,$max,in_array($key,['source_url','creator'],true));
    echo $r->select('licence',$d,grassguideinput::LICENCES,true,true).$r->field('rights_note',$d,3000,false,true).$r->select('state',$d,['active','archived'],false).'<p>'.$t('licence_help').'</p><div class="chisimba-form-actions">'.$r->button('save','save').'</div></fieldset></form>';
    if ($grassRecord && $grassRecord['kind']==='image') echo '<figure><img src="'.$e($grassRecord['image_url']).'" alt="'.$e($grassRecord['alt_text']).'" width="480" loading="lazy" referrerpolicy="no-referrer"><figcaption>'.$e($grassRecord['caption']).' — '.$e($grassRecord['creator']).' ('.$e($grassRecord['licence']).')</figcaption></figure>';
    if ($grassRecord) echo '<p><a target="_blank" rel="noopener noreferrer" href="'.$e($grassRecord['source_url']).'">'.$t('open_source').'</a></p>';
    if ($grassRecord && $grassRecord['kind']==='youtube') echo '<p><a target="_blank" rel="noopener noreferrer" href="https://www.youtube.com/watch?v='.$e($grassRecord['youtube_id']).'">'.$t('watch_video').'</a></p>';
}
echo '</main><div id="grassguide-labels" hidden data-saving="'.$t('saving').'" data-failed="'.$t('network_failed').'" data-saved="'.$t('saved').'"></div>';
echo $this->getJavaScriptFile('admin.js','grassguide');
