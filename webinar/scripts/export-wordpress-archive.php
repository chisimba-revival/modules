<?php
/** Read-only full archive export; excludes audience data and operational metadata. */
if (PHP_SAPI !== 'cli') exit(1);
define('SHORTINIT', true);
require '/var/www/learnthebirds.com/wp-load.php';
global $wpdb;
function field($id,$key) {
    global $wpdb;
    return (string)$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id DESC LIMIT 1",$id,$key));
}
function ids($raw) {
    $value=@unserialize($raw,['allowed_classes'=>false]);
    if (!is_array($value)) $value=ctype_digit($raw)?[$raw]:[];
    return array_values(array_unique(array_filter(array_map(static fn($v)=>is_scalar($v)&&ctype_digit((string)$v)?(int)$v:0,$value))));
}
$rows=$wpdb->get_results("SELECT ID,post_title,post_content,post_name,post_status FROM {$wpdb->posts} WHERE post_type='event_listing' AND post_status IN ('publish','expired') ORDER BY ID DESC",ARRAY_A);
$candidates=[];
foreach($rows as $row) {
    $date=field($row['ID'],'_event_start_date');
    $stamp=strtotime($date);
    if(!$stamp || $stamp>=strtotime('today') || in_array(strtolower(field($row['ID'],'_cancelled')),['1','yes','true'],true))continue;
    $row['actual_date']=$date;
    $row['recording_url']=field($row['ID'],'_event_video_url');
    $row['alternate_video']=field($row['ID'],'_learndash_course_grid_video_embed_code');
    $row['speaker_ids']=ids(field($row['ID'],'_event_organizer_ids'));
    $candidates[]=$row;
}
$selected=[];foreach($candidates as $row){$row['source_status']=$row['post_status'];$row['post_status']='publish';$selected[$row['ID']]=$row;}
$out=['source'=>'https://learnthebirds.com','version'=>1,'events'=>[],'speakers'=>[],'attachments'=>[],'issues'=>[]];
function attachment($id) {
    global $out;
    $id=(int)$id;if(!$id)return null;
    $path=field($id,'_wp_attached_file');
    $root=realpath('/var/www/learnthebirds.com/wp-content/uploads');
    $real=realpath($root.'/'.$path);
    $valid=$path!==''&&$real&&str_starts_with($real,$root.'/')&&is_file($real);
    $out['attachments'][$id]=['source_id'=>$id,'relative_path'=>$path,'exists'=>(bool)$valid,'sha256'=>$valid?hash_file('sha256',$real):null,'bytes'=>$valid?filesize($real):null];
    if(!$valid)$out['issues'][]='Missing attachment '.$id;
    return $id;
}
foreach($selected as $row) {
    $id=$row['ID'];
    $row['featured_attachment_id']=attachment(field($id,'_thumbnail_id'));
    $raw=field($id,'_event_banner');
    $decoded=@unserialize($raw,['allowed_classes'=>false]);
    $banner=is_array($decoded)?reset($decoded):$raw;
    $row['banner_reference']=is_string($banner)?$banner:'';
    $relative=parse_url($row['banner_reference'],PHP_URL_PATH);
    $relative=is_string($relative)?preg_replace('~^/wp-content/uploads/~','',$relative):'';
    $bannerId=$wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value=%s LIMIT 1",$relative));
    $row['banner_attachment_id']=attachment($bannerId);
    if(!$bannerId)$out['issues'][]='Unresolved banner '.$id;
    $row['timezone']=field($id,'_event_timezone')?:'Africa/Johannesburg';
    foreach($row['speaker_ids'] as $speakerId) {
        if(isset($out['speakers'][$speakerId]))continue;
        $speaker=$wpdb->get_row($wpdb->prepare("SELECT ID,post_name,post_status FROM {$wpdb->posts} WHERE ID=%d AND post_type='event_organizer'",$speakerId),ARRAY_A);
        if(!$speaker){$out['issues'][]='Missing speaker '.$speakerId;continue;}
        $speaker['name']=field($speakerId,'_organizer_name');
        $speaker['biography']=field($speakerId,'_organizer_description');
        $speaker['photo_attachment_id']=attachment(field($speakerId,'_thumbnail_id'));
        $speaker['logo_reference']=field($speakerId,'_organizer_logo');
        $out['speakers'][$speakerId]=$speaker;
    }
    $out['events'][]=$row;
}
// Preserve local inline images and responsive variants, not just card images.
$out['inline_media']=[];
function inlineMedia($html) {
 global $out;
 preg_match_all('~https?://(?:www\.)?learnthebirds\.com/wp-content/uploads/[^\s"\'<>]+~i',$html,$matches);
 foreach($matches[0] as $url) {
  $url=html_entity_decode($url,ENT_QUOTES,'UTF-8');$path=rawurldecode(substr(parse_url($url,PHP_URL_PATH),strlen('/wp-content/uploads/')));
  $root=realpath('/var/www/learnthebirds.com/wp-content/uploads');$real=realpath($root.'/'.$path);
  if(!$real||!str_starts_with($real,$root.'/')||!is_file($real)){$out['issues'][]='Missing inline media '.$path;continue;}
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($real);
  if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true))continue;
  $key='inline_'.substr(hash('sha256',$path),0,24);
  $out['attachments'][$key]=['source_id'=>$key,'relative_path'=>$path,'exists'=>true,'sha256'=>hash_file('sha256',$real),'bytes'=>filesize($real)];
  $out['inline_media'][$url]=$key;
 }
}
foreach($out['events'] as $row)inlineMedia($row['post_content']);
foreach($out['speakers'] as $row){inlineMedia($row['biography']);inlineMedia($row['logo_reference']);}
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
