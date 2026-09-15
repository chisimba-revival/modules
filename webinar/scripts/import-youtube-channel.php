<?php
/** Replace one reviewed channel snapshot atomically; never downloads videos. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Migration staging only');
$input=json_decode(file_get_contents('/tmp/ltb-youtube-videos.json'),true,512,JSON_THROW_ON_ERROR);$channel=$input['channel'];
if(!preg_match('/^UC[A-Za-z0-9_-]{22}$/D',$channel)||$input['source']!=='https://www.youtube.com/channel/'.$channel||!$input['videos']||count($input['videos'])>10000)throw new RuntimeException('Invalid source');
$ids=[];foreach($input['videos'] as $v){if(!preg_match('/^[A-Za-z0-9_-]{11}$/D',$v['id'])||isset($ids[$v['id']])||in_array($v['id'],$input['excluded_shorts'],true)||!in_array($v['selection'],['videos','streams'],true)||!preg_match('/^[0-9]+(?::[0-9]{2}){1,2}$/D',$v['duration'])||!is_string($v['title'])||mb_strlen($v['title'])>500)throw new RuntimeException('Invalid video');$ids[$v['id']]=1;}
if(!in_array('--apply',$argv,true)){echo 'Dry run: '.count($ids).' videos, '.count($input['excluded_shorts'])." Shorts excluded.\n";exit;}
$store=$e->getObject('webinarvideos','webinar');$db=$e->getDbObj();$indexes=$store->query('SHOW INDEX FROM tbl_webinar_videos');$unique=[];foreach($indexes as $index)if(!$index['non_unique'])$unique[$index['key_name']]=1;if(!isset($unique['video_identity_unique'],$unique['video_channel_unique']))throw new RuntimeException('Run configure-publication-imports.php first');$q=method_exists($db,'quoteSmart')?$db->quoteSmart($channel):$db->quote($channel);$begin=$db->beginTransaction();if(PEAR::isError($begin))throw new RuntimeException('Cannot start catalogue transaction');
try{
 if($store->query('DELETE FROM tbl_webinar_videos WHERE channel_id='.$q)===false)throw new RuntimeException('Replacement failed');
 foreach($input['videos'] as $position=>$v){
  $row=['id'=>substr(hash('sha256',$channel.'|'.$v['id']),0,32),'channel_id'=>$channel,'video_id'=>$v['id'],'title'=>$v['title'],'duration'=>$v['duration'],'position'=>$position,'selection'=>$v['selection'],'imported_at'=>gmdate('Y-m-d H:i:s')];
  $insert=$db->exec('INSERT INTO tbl_webinar_videos ('.implode(',',array_keys($row)).') VALUES ('.implode(',',array_map(fn($value)=>$db->quote($value),array_values($row))).')');if(PEAR::isError($insert))throw new RuntimeException('Video insert failed');
 }
 $saved=$store->query('SELECT video_id FROM tbl_webinar_videos WHERE channel_id='.$q);if(count($saved)!==count($ids))throw new RuntimeException('Stored video count mismatch');
 $commit=$db->commit();if(PEAR::isError($commit))throw new RuntimeException('Cannot commit catalogue');
}catch(Throwable $error){$db->rollback();throw $error;}
$e->getObject('dbsysconfig','sysconfig')->changeParam('YOUTUBE_CHANNEL','webinar',$channel);echo 'Imported '.count($ids)." videos.\n";
