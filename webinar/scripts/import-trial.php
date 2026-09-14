<?php
/** CLI-only migration adapter. Run against protected staging after reviewing the batch. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;
require 'classes/core/engine_class_inc.php';$e=new engine();
$config=$e->getObject('altconfig','config');
if(parse_url($config->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Trial restricted to migration site');
$input=json_decode(file_get_contents('/tmp/webinar-trial/trial-export.json'),true,512,JSON_THROW_ON_ERROR);
if($input['source']!=='https://learnthebirds.com'||$input['issues'])throw new RuntimeException('Unreviewed source');
$apply=in_array('--apply',$argv,true);
$files=$e->getObject('dbfile','filemanager');$folders=$e->getObject('dbfolder','filemanager');$urls=[];$fileIds=[];
foreach($input['attachments'] as $id=>$a){
 $src=realpath('/tmp/webinar-trial/media/'.$a['relative_path']);
 if(!$src||!str_starts_with($src,'/tmp/webinar-trial/media/')||hash_file('sha256',$src)!==$a['sha256'])throw new RuntimeException('Media mismatch');
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
$store=$e->getObject('webinarstore','webinar');$clean=$e->getObject('richtextsanitizer','utilities');$results=[];
function identity($kind,$id){return substr(hash('sha256','learnthebirds.com|'.$kind.'|'.$id),0,32);}
function saveRecord($kind,$sourceId,$title,$date,$payload){global $store,$results;$encoded=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$results[]= $store->importRecord(['id'=>identity($kind,$sourceId),'kind'=>$kind,'source_key'=>'learnthebirds.com|'.$kind.'|'.$sourceId,'source_hash'=>hash('sha256',$title.'|'.$date.'|'.$encoded),'title'=>$title,'status'=>'published','presented_at'=>$date,'payload'=>$encoded]);}
foreach($input['speakers'] as $id=>$s){if($s['post_status']!=='publish')throw new RuntimeException('Nonpublic speaker');saveRecord('speaker',$id,$s['name'],'',['description'=>$clean->cleanHtml($s['biography']),'image'=>$urls[$s['photo_attachment_id']]??'','image_file_id'=>$fileIds[$s['photo_attachment_id']]??null,'source_slug'=>$s['post_name']]);}
foreach($input['events'] as $w){
 if($w['post_status']!=='publish')throw new RuntimeException('Nonpublic webinar');
 $zone=new DateTimeZone($w['timezone']);$date=new DateTimeImmutable($w['actual_date'],$zone);
 $imageId=$w['banner_attachment_id']?:$w['featured_attachment_id'];
 saveRecord('webinar',$w['ID'],$w['post_title'],$date->format('Y-m-d H:i:s'),['description'=>$clean->cleanHtml($w['post_content']),'image'=>$urls[$imageId]??'','image_file_id'=>$fileIds[$imageId]??null,'legacy_thumbnail_file_id'=>$fileIds[$w['featured_attachment_id']]??null,'recording'=>$w['recording_url'],'timezone'=>$zone->getName(),'speakers'=>array_map(fn($id)=>identity('speaker',$id),$w['speaker_ids']),'source_slug'=>$w['post_name']]);
}
echo json_encode(array_count_values($results))."\n";
