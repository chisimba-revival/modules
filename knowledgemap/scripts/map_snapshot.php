<?php
/** Explicit local maintenance: backup/restore one map. @author Derek Keats */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_MAP_MAINTENANCE')!=='1')exit(64);
if(count($argv)!==4||!in_array($argv[1],['backup','restore'],true)){fwrite(STDERR,"Usage: map_snapshot.php backup|restore MAP_ID ABSOLUTE_JSON_PATH\n");exit(64);}
chdir(dirname(__DIR__,3));$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';
try{
 $engine=new engine();$service=$engine->getObject('knowledgemapsnapshot','knowledgemap');$path=$argv[3];if(!str_starts_with($path,'/'))throw new InvalidArgumentException('Absolute archive path required');
 if($argv[1]==='backup'){
  $raw=json_encode($service->capture($argv[2]),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$file=fopen($path,'x');if(!$file)throw new RuntimeException('Backup already exists or cannot be created');chmod($path,0600);fwrite($file,$raw);fclose($file);file_put_contents($path.'.sha256',hash('sha256',$raw)."\n");echo "Map snapshot saved\n";
 }else{
  $raw=file_get_contents($path);$expected=trim(file_get_contents($path.'.sha256'));if(!hash_equals($expected,hash('sha256',$raw)))throw new RuntimeException('Snapshot checksum mismatch');
  $revision=$service->restore(json_decode($raw,true,512,JSON_THROW_ON_ERROR),$argv[2]);echo 'Map restored at revision '.$revision."\n";
 }
}catch(Throwable $error){fwrite(STDERR,$error->getMessage()."\n");exit(1);}
