<?php
/** Daily pending-registration retention worker. Defaults to a read-only preview. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? '--preview';
if (!in_array($mode, array('--preview','--apply'), true)) { fwrite(STDERR,"Use --preview or --apply.\n"); exit(64); }
chdir(dirname(__DIR__, 3));
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require_once 'classes/core/engine_class_inc.php';
try {
    $engine=new engine();
    $cleanup=$engine->getObject('registrationcleanup','registration-service');
    $summary=$mode==='--apply' ? $cleanup->run() : $cleanup->preview();
    echo json_encode($summary, JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(empty($summary['failed']) ? 0 : 1);
} catch (Throwable $exception) { fwrite(STDERR,"Registration cleanup failed; no successful completion was recorded.\n"); exit(1); }
