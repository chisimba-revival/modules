<?php
/** Run from an installed Chisimba site; batch size 1–10. @author Derek Keats */
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$limit=isset($argv[1])?filter_var($argv[1],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>10]]):1;
if ($limit===false) { fwrite(STDERR,"Batch size must be 1–10.\n"); exit(64); }
chdir(dirname(__DIR__,3));
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost'; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require_once 'classes/core/engine_class_inc.php';
try {
    $worker=(new engine())->getObject('spokenworker','spokenassessment'); $count=0;
    while ($count<$limit) { $result=$worker->runOne(); if (!$result['selected']) break; ++$count; }
    echo json_encode(['processed'=>$count]).PHP_EOL;
} catch (Throwable $e) { fwrite(STDERR,"Spoken assessment worker failed. Check service configuration.\n"); exit(1); }
