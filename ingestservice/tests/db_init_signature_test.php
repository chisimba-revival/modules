<?php
$GLOBALS['kewl_entry_point_run'] = true;
class dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback') {}
}
require dirname(__DIR__) . '/classes/db_ingestservice_runs_class_inc.php';
$method = new ReflectionMethod('db_ingestservice_runs', 'init');
if ($method->getNumberOfParameters() !== 3 || $method->getNumberOfRequiredParameters() !== 0) {
    fwrite(STDERR, "FAIL: incompatible dbTable initializer signature\n");
    exit(1);
}
echo "OK: compatible dbTable initializer signature\n";
