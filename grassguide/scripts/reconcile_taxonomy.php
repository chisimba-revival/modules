<?php
/** Trusted operator CLI: review dry-run output before --apply; never exposed over HTTP. */
if(PHP_SAPI!=='cli')exit(64);
$root=dirname(__DIR__,3);chdir($root);$GLOBALS['kewl_entry_point_run']=true;
$_SERVER+=['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'localhost','SCRIPT_NAME'=>'/index.php','QUERY_STRING'=>''];
require_once $root.'/classes/core/engine_class_inc.php';$engine=new engine();
$plan=json_decode(file_get_contents(__DIR__.'/../data/powo-reconciliation-20261127.json'),true,64,JSON_THROW_ON_ERROR);
echo json_encode($engine->getObject('grassguidestore','grassguide')->reconcileTaxonomy($plan,'powo-reconciliation',in_array('--apply',$argv,true)),JSON_THROW_ON_ERROR).PHP_EOL;
