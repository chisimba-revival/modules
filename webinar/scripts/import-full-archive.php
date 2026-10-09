<?php
/** CLI-only full archive adapter with guarded reconciliation of inline media. Run against protected staging after reviewing the batch. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;
require 'classes/core/engine_class_inc.php';$e=new engine();
$config=$e->getObject('altconfig','config');
if(parse_url($config->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Trial restricted to migration site');
$input=json_decode(file_get_contents('/tmp/webinar-full/trial-export.json'),true,512,JSON_THROW_ON_ERROR);
if($input['source']!=='https://learnthebirds.com'||$input['issues'])throw new RuntimeException('Unreviewed source');
$apply=in_array('--apply',$argv,true);
$files=$e->getObject('dbfile','filemanager');$folders=$e->getObject('dbfolder','filemanager');$urls=[];$fileIds=[];
foreach($input['attachments'] as $id=>$a){
 $src=realpath('/tmp/webinar-full/media/'.$a['relative_path']);
 if(!$src||!str_starts_with($src,'/tmp/webinar-full/media/')||hash_file('sha256',$src)!==$a['sha256'])throw new RuntimeException('Media mismatch');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($src);
 if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true))throw new RuntimeException('Unsupported image');
 $path='users/ltb-migration-admin/learnthebirds/'.$a['relative_path'];
 if(!$apply)continue;
 $dst=rtrim($config->getcontentBasePath(),'/').'/'.$path;
 if(!is_dir(dirname($dst))&&!mkdir(dirname($dst),0775,true))throw new RuntimeException('Cannot create media folder');
 if(is_file($dst)&&hash_file('sha256',$dst)!==$a['sha256'])throw new RuntimeException('Media conflict');
 if(!is_file($dst)&&!copy($src,$dst))throw new RuntimeException('Media copy failed');
 $folders->indexFolder(dirname($path),false);
 $record=$files->getFileDetailsFromPath($path);
 $fid=$record?$record['id']:$files->addFile(basename($path),$path,filesize($dst),$mime,'images',1,'ltb-migration-admin');
 if(!$fid)throw new RuntimeException('File registration failed');
 $files->setFileAccess($fid,'public');
 $fileIds[$id]=$fid;
 $urls[$id]=$e->uri(['action'=>'file','id'=>$fid,'filename'=>basename($path)],'filemanager');
}
if(!$apply){echo "Dry run: ".count($input['events'])." webinars, ".count($input['speakers'])." speakers, ".count($input['attachments'])." verified images. No writes.\n";exit;}
$inlineUrls=[];foreach(($input['inline_media']??[]) as $url=>$key){$inlineUrls[$url]=$urls[$key];}
$store=$e->getObject('webinarstore','webinar');$clean=$e->getObject('richtextsanitizer','utilities');$results=[];$pending=[];
function identity($kind,$id){return substr(hash('sha256','learnthebirds.com|'.$kind.'|'.$id),0,32);}
function saveRecord($kind,$sourceId,$title,$date,$payload,$originalPayload=null){global $pending;$encoded=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$candidate=['id'=>identity($kind,$sourceId),'kind'=>$kind,'source_key'=>'learnthebirds.com|'.$kind.'|'.$sourceId,'source_hash'=>hash('sha256',$title.'|'.$date.'|'.$encoded),'title'=>$title,'status'=>'published','presented_at'=>$date,'payload'=>$encoded];
 $pending[]=[$candidate,$originalPayload===null?null:hash('sha256',$title.'|'.$date.'|'.json_encode($originalPayload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES))];
}

function description($html){global $clean,$inlineUrls;return $clean->cleanHtml(strtr($html,$inlineUrls));}

foreach($input['speakers'] as $id=>$s){
 if($s['post_status']!=='publish')throw new RuntimeException('Nonpublic speaker');
 $original=['description'=>$clean->cleanHtml($s['biography']),'image'=>$urls[$s['photo_attachment_id']]??'','image_file_id'=>$fileIds[$s['photo_attachment_id']]??null,'source_slug'=>$s['post_name']];
 $payload=$original;$payload['description']=description($s['biography']);
 saveRecord('speaker',$id,$s['name'],'',$payload,$original);
}
foreach($input['events'] as $w){
 if($w['post_status']!=='publish')throw new RuntimeException('Nonpublic webinar');
 $zone=new DateTimeZone($w['timezone']);$date=new DateTimeImmutable($w['actual_date'],$zone);
 $imageId=$w['banner_attachment_id']?:$w['featured_attachment_id'];
 $extra=[];
 if(($w['trial_case']??'')==='upcoming'){
  $end=new DateTimeImmutable($w['end_date'],$zone);
  if($end<=$date||$date->format('H:i')!==$w['start_time']||$end->format('H:i')!==$w['end_time'])throw new RuntimeException('Inconsistent event times');
  $extra=['ends_at'=>$end->format('Y-m-d H:i:s'),'registration_open'=>false];
 }
 $original=['description'=>$clean->cleanHtml($w['post_content']),'image'=>$urls[$imageId]??'','image_file_id'=>$fileIds[$imageId]??null,'legacy_thumbnail_file_id'=>$fileIds[$w['featured_attachment_id']]??null,'recording'=>$w['recording_url'],'timezone'=>$zone->getName(),'speakers'=>array_map(fn($id)=>identity('speaker',$id),$w['speaker_ids']),'source_slug'=>$w['post_name']]+$extra;
 $payload=$original;$payload['description']=description($w['post_content']);
 saveRecord('webinar',$w['ID'],$w['post_title'],$date->format('Y-m-d H:i:s'),$payload,$original);
}
// Validate the complete batch before publishing any new records.
foreach($pending as [$candidate,$originalHash]){
 $old=$store->getRow('source_key',$candidate['source_key']);
 if($old && ($old['status']!=='published'||($old['source_hash']!==$candidate['source_hash']&&$old['source_hash']!==$originalHash)))throw new RuntimeException('Review source conflict: '.$candidate['source_key']);
}
$db=$e->getDbObj();$begin=$db->beginTransaction();if(PEAR::isError($begin))throw new RuntimeException('Cannot begin archive transaction');
try {
 foreach($pending as [$candidate,$originalHash]){
  $old=$store->getRow('source_key',$candidate['source_key']);
  if($old && $old['source_hash']!==$candidate['source_hash']){
   if(!hash_equals($originalHash,$old['source_hash']))throw new RuntimeException('Concurrent archive edit');
   if($store->update('id',$candidate['id'],['payload'=>$candidate['payload'],'source_hash'=>$candidate['source_hash']])===false)throw new RuntimeException('Inline reconciliation failed');
   $results[]='inline_media_updated';
  } else $results[]=$store->importRecord($candidate);
 }
 $commit=$db->commit();if(PEAR::isError($commit))throw new RuntimeException('Archive commit failed');
} catch(Throwable $error){$db->rollback();throw $error;}
echo json_encode(array_count_values($results))."\n";
