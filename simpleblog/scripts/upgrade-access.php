<?php
/** Explicit CLI upgrade through the existing module registration API. */
if (PHP_SAPI !== 'cli' || empty($argv[1])) exit(1);
chdir($argv[1]);
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require_once 'classes/core/engine_class_inc.php';
$engine=new engine();
$store=$engine->getObject('publishingstore','simpleblog');
if ($store->query('ALTER TABLE tbl_simpleblog_posts ADD COLUMN IF NOT EXISTS required_tier_code VARCHAR(32) NULL')===false) throw new RuntimeException('Access schema upgrade failed');
$files=$engine->getObject('modulefile','modulecatalogue');
$admin=$engine->getObject('modulesadmin','modulecatalogue');
foreach (['membership-service','payment-service','simpleblog'] as $module) {
    $data=$files->readRegisterFile($files->findRegisterFile($module));
    if (!$admin->installModule($data,true)) throw new RuntimeException('Module upgrade failed: '.$module);
    echo $module." upgraded\n";
}
