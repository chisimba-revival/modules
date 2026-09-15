<?php
/** CLI media migration through managed files; identical paths are safely reused. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
function migrationMedia($engine,array $attachments,$mediaRoot,$apply)
{
 $config=$engine->getObject('altconfig','config');$files=$engine->getObject('dbfile','filemanager');$folders=$engine->getObject('dbfolder','filemanager');$root=realpath($mediaRoot);if(!$root)throw new RuntimeException('Missing media package');$urls=[];
 foreach($attachments as $key=>$a){
  $source=realpath($root.'/'.$a['relative_path']);if(!$source||!str_starts_with($source,$root.'/')||hash_file('sha256',$source)!==$a['sha256'])throw new RuntimeException('Source media mismatch '.$key);
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($source);if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true))throw new RuntimeException('Unsupported media '.$a['relative_path']);
  $path='users/ltb-migration-admin/learnthebirds/'.$a['relative_path'];
  if(!$apply){$urls[$key]='usrfiles/'.$path;continue;}
  $destination=rtrim($config->getcontentBasePath(),'/').'/'.$path;
  if(is_file($destination)&&hash_file('sha256',$destination)!==$a['sha256'])throw new RuntimeException('Existing media conflict '.$path);
  if(!is_dir(dirname($destination))&&!mkdir(dirname($destination),0775,true))throw new RuntimeException('Cannot create media folder');
  if(!is_file($destination)&&!copy($source,$destination))throw new RuntimeException('Cannot copy media');
  $folders->indexFolder(dirname($path),false);$old=$files->getFileDetailsFromPath($path);$id=$old?$old['id']:$files->addFile(basename($path),$path,filesize($destination),$mime,'images',1,'ltb-migration-admin');
  if(!$id)throw new RuntimeException('File indexing failed');$files->setFileAccess($id,'public');
  $urls[$key]=html_entity_decode($engine->uri(['action'=>'file','id'=>$id,'filename'=>basename($path)],'filemanager'),ENT_QUOTES,'UTF-8');
 }
 return $urls;
}
