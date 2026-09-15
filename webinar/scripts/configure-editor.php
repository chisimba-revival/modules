<?php
/** Staging schema/capability gate before allowing transactional editorial writes. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Migration staging only');
$e->getObject('webinareditstore','webinar')->prepareSchema();
$p=$e->getObject('permissionservice','security');$area=$p->ensureArea('chisimba','webinar');if(!$p->ensureRight($area,'manage'))throw new RuntimeException('Cannot define editor capability');
echo "Webinar editor transactional identity and management capability ready; no grants added.\n";
