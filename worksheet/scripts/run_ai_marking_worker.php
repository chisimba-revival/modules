<?php
/** Process bounded AI worksheet-marking jobs from the command line. */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(64); }
$siteRoot = dirname(__DIR__, 3);
chdir($siteRoot);
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';
$GLOBALS['kewl_entry_point_run'] = true;
require_once 'classes/core/engine_class_inc.php';
try {
    $worker = (new engine())->getObject('dbworksheetaimarkingjobs', 'worksheet');
    $limit = isset($argv[1]) ? filter_var($argv[1], FILTER_VALIDATE_INT, array('options'=>array('min_range'=>1, 'max_range'=>20))) : 5;
    if ($limit === false) { throw new InvalidArgumentException('Batch size must be from 1 to 20.'); }
    $summary = array('selected'=>0, 'completed'=>0);
    for ($step=0; $step<$limit; $step++) {
        $result = $worker->runOne();
        if (empty($result['selected'])) { break; }
        $summary['selected']++; $summary['completed'] += (int) ($result['completed'] ?? 0);
    }
    echo json_encode($summary, JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Worksheet AI marking worker failed: '.$exception->getMessage().PHP_EOL); exit(1);
}
?>
