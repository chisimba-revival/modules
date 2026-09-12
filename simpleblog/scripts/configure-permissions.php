<?php
/** Define publishing capabilities in the canonical module area after module installation. */
if(PHP_SAPI!=='cli')exit(1);
$app=$argv[1]??dirname(__DIR__,3).'/framework/app';
if(!is_file($app.'/classes/core/engine_class_inc.php'))throw new RuntimeException('Pass the Chisimba application directory');
chdir($app);$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;require_once 'classes/core/engine_class_inc.php';$engine=new engine();
$permissions=$engine->getObject('permissionservice','security');$area=$permissions->ensureArea('chisimba','simpleblog');
foreach(['personal_publish','site_publish','site_manage'] as $name) {
 if(!$permissions->ensureRight($area,$name))throw new RuntimeException('Permission definition failed');
}
echo "Publishing permissions defined. Assign explicit grants through Chisimba permissions administration.\n";
