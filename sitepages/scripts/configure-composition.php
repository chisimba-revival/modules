<?php
/** Explicit, repeatable page-composition upgrade. Run after Module Catalogue update. */
if(PHP_SAPI!=='cli')exit(1);
$root=$argv[1]??'';if(!$root||!is_file($root.'/classes/core/engine_class_inc.php'))throw new RuntimeException('Supply the intended application root');
chdir($root);$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']=$argv[2]??'localhost';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['PHP_SELF']='/ch/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();$changes=$e->getObject('sitepagesmaintenance','sitepages')->reconcile(true);
echo "Page composition schema ready; existing content preserved.\n";
