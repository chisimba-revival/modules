<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$contacts=$this->getObject('grassguidecontacts','grassguide');$groups=$contacts->groups();
echo '<h2>'.$t('contributors').'</h2><p>'.$t('contact_intro').'</p><div class="chisimba-form-actions">'.$r->link('contributors_export','download',['action'=>'contributorsexport']).$r->link('rights','arrow-left',['action'=>'rights']).'</div>';
echo '<p>'.sprintf($t('contributors_count'),count($groups),array_sum(array_map(fn($g)=>count($g['items']),$groups))).'</p>';
$selected=$grassContributor?:($groups[0]['id']??'');$group=null;
echo '<form method="get" class="chisimba-form chisimba-form-actions" action="'.$e($this->uri([],'grassguide')).'">'.$hidden('module','grassguide').$hidden('action','contributors').'<div class="chisimba-form-field"><label for="gg-contributor">'.$t('choose_contributor').'</label><select name="contributor" id="gg-contributor">';
foreach($groups as $g) {
    if($selected===$g['id'])$group=$g;
    echo '<option value="'.$e($g['id']).'"'.($selected===$g['id']?' selected':'').'>'.$e(($g['creator']?:$r->text('unknown_contributor')).' ('.count($g['items']).')').'</option>';
}
echo '</select></div>'.$r->button('view_contributor','users').'</form>';
if($group) {
    $id=$group['id'];$saved=$contacts->contact($id);$d=$grassContactInput??$saved;$preview=$contacts->preview($id);$history=$contacts->history($id);$pending=count(array_filter($history,fn($h)=>$h['delivery_status']==='queued'))>0;
    echo '<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h3>'.$e($group['creator']).'</h3><p>'.sprintf($t('contributor_counts'),count($group['photos']),count($group['species']),$group['needs_permission']).'</p>';
    $images=function()use($group,$e,$t,$r) {
        $html='<div class="chisimba-story-grid">';
        foreach($group['items'] as $item)$html.='<article class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h4><i>'.$e($item['name']).'</i></h4><img src="'.$e($item['content']['image_url']).'" alt="'.$e($item['content']['alt_text']).'" width="180" loading="lazy" referrerpolicy="no-referrer"><p>'.$e($item['licence']).' · '.$t(grassguiderights::eligible($item,'commercial')?'clearance_recorded':'clearance_needed').'</p><div class="chisimba-form-actions"><a class="button" href="'.$e($item['source_url']).'" target="_blank" rel="noopener noreferrer">'.$t('open_source').'</a>'.$r->link('rights_task','pencil',['action'=>'rightstask','target'=>$item['target']]).'</div></article>';
        return $html.'</div>';
    };
    echo '<button type="button" class="button" hidden data-grass-dialog="gg-contributor-images">'.$t('view_images').'</button><dialog class="chisimba-content-dialog" id="gg-contributor-images" aria-labelledby="gg-images-title"><button type="button" class="button" data-close-dialog>'.$t('close_images').'</button><h3 id="gg-images-title">'.$e($group['creator']).' — '.$t('view_images').'</h3>'.$images().'</dialog><details id="gg-contributor-images-inline"><summary>'.$t('view_images').'</summary>'.$images().'</details>';
    echo '<h3>'.$t('contact_draft').'</h3><p>'.$t('contact_private').'</p>'.$start('savecontact',$id,$d['revision']).$r->field('email',$d,320).$r->field('subject',$d,200,true).$r->field('opening',$d,3000,false,true).$r->field('request_body',$d,8000,true,true).$r->field('signature',$d,500,true,true).'<div class="chisimba-form-actions">'.$r->button('save_contact','save').'</div></form></section>';
    echo '<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h3>'.$t('permission_preview').'</h3><p>'.$t('preview_saved_notice').'</p><p><strong>'.$t('mail_from').'</strong> '.$e($preview['sender']['name'].' <'.$preview['sender']['email'].'>').'<br><strong>'.$t('mail_to').'</strong> '.$e($preview['to']).'<br><strong>'.$t('subject').'</strong> '.$e($preview['subject']).'</p><div class="chisimba-flow chisimba-prose">'.nl2br($e($preview['body'])).'</div>';
    $ready=$saved['revision']>0&&filter_var($saved['email'],FILTER_VALIDATE_EMAIL)&&$preview['photos']&&!$pending&&$preview['sender']['transport']==='sendgrid';
    if($ready)echo $start('queuepermission',$id,$saved['revision']).$hidden('request_id',bin2hex(random_bytes(16))).$hidden('fingerprint',$preview['fingerprint']).'<p>'.$t('queue_notice').'</p>'.$r->button($history?'queue_again':'queue_permission','send').'</form>';
    else echo '<p role="status">'.$t($pending?'permission_already_pending':(!$preview['photos']?'no_permission_images':'save_before_queue')).'</p>';
    echo '</section><section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h3>'.$t('permission_history').'</h3><p>'.$t('permission_history_help').'</p>';
    foreach($history as $h) {
        $status=in_array($h['delivery_status'],['queued','sent','failed','cancelled'],true)?$h['delivery_status']:'unknown';
        echo '<details><summary>'.$e($h['queued_at']).' UTC · '.$t('mail_'.$status).' · '.$e($h['to']).'</summary><p><strong>'.$e($h['subject']).'</strong></p><div class="chisimba-prose">'.nl2br($e($h['body'])).'</div></details>';
    }
    if(!$history)echo '<p>'.$t('no_permission_history').'</p>';
    echo '</section>';
}
