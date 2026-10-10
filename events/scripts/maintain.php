<?php
/** Queue due event messages and reconcile tickets. Does not send mail itself.
 * @author Derek Keats <derek@dkeats.com>
 */
if(PHP_SAPI!=='cli') exit(64);
$root=$argv[1]??'';
if(!$root||!is_file($root.'/classes/core/engine_class_inc.php')) { fwrite(STDERR,"Usage: php maintain.php /absolute/installed/site/root\n"); exit(64); }
chdir($root); $_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost'; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true; require 'classes/core/engine_class_inc.php';
try {
    $engine=new engine(); $s=$engine->getObject('eventservice','events'); $store=$engine->getObject('eventstore','events'); $count=0;
    foreach($store->rows('occurrences') as $o) { $s->maintain($o['id']); ++$count; }
    echo json_encode(['occurrences_processed'=>$count]).PHP_EOL;
} catch(Throwable $error) { fwrite(STDERR,"Event maintenance failed; inspect the local application diagnostics.\n"); exit(1); }
