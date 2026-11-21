<?php
/** Trusted operator CLI only. Uses the same saved policy as the administrator download. */
if(PHP_SAPI!=='cli')exit(64);
$root=dirname(__DIR__,3);chdir($root);$GLOBALS['kewl_entry_point_run']=true;
$_SERVER+=['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'localhost','SCRIPT_NAME'=>'/index.php','QUERY_STRING'=>''];
require_once $root.'/classes/core/engine_class_inc.php';$engine=new engine();
echo json_encode($engine->getObject('grassguiderights','grassguide')->operatorExport(),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
