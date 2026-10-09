<?php
/** Complete identity constraints omitted by the legacy index installer. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Migration staging only');
$db=$e->getDbObj();
// Module registration and SQL upgrades are separate in the legacy installer.
// Apply only the new nullable import columns that are missing; preserve all content.
$result=$db->query('SHOW COLUMNS FROM tbl_simpleblog_posts');if(PEAR::isError($result))throw new RuntimeException('Missing article table');
$columns=array_column($result->fetchAll(MDB2_FETCHMODE_ASSOC),'field');
foreach(['author_credit'=>'VARCHAR(250)','source_key'=>'VARCHAR(191)','source_url'=>'LONGTEXT','source_hash'=>'VARCHAR(64)','import_hash'=>'VARCHAR(64)'] as $column=>$type){
 if(!in_array($column,$columns,true)){$r=$db->exec('ALTER TABLE tbl_simpleblog_posts ADD COLUMN '.$column.' '.$type.' NULL');if(PEAR::isError($r))throw new RuntimeException('Cannot add '.$column);}
}
foreach(['tbl_webinar_videos'=>['video_identity_unique'=>'id','video_channel_unique'=>'channel_id,video_id'],'tbl_simpleblog_posts'=>['publication_identity_unique'=>'id','simpleblog_source_unique'=>'source_key']] as $table=>$indexes){
 $result=$db->query('SHOW INDEX FROM '.$table);if(PEAR::isError($result))throw new RuntimeException('Missing publication table');$rows=$result->fetchAll(MDB2_FETCHMODE_ASSOC);
 foreach($indexes as $name=>$columns){$found=false;foreach($rows as $r)if($r['key_name']===$name&&!$r['non_unique'])$found=true;if(!$found){$result=$db->exec('ALTER TABLE '.$table.' ADD UNIQUE KEY '.$name.' ('.$columns.')');if(PEAR::isError($result))throw new RuntimeException('Cannot establish '.$name);}}
}
echo "Publication identity constraints verified.\n";
