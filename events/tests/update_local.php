<?php
/** Explicit development-only catalogue update for the local integration harness.
 * @author Derek Keats <derek@dkeats.com>
 */
if(PHP_SAPI!=='cli'||!getenv('EVENTS_TEST_SITE')) exit(64);
chdir(getenv('EVENTS_TEST_SITE')); $_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost'; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true; require 'classes/core/engine_class_inc.php'; $engine=new engine();
$f=$engine->getObject('modulefile','modulecatalogue'); $admin=$engine->getObject('modulesadmin','modulecatalogue'); $modules=$engine->getObject('modules','modulecatalogue');
foreach(['host-service','events'] as $name) {
    $record=$f->readRegisterFile($f->findRegisterFile($name));
    $admin->installModule($record,(bool)$modules->checkIfRegistered($name));
    $engine->getPatchObject($name)->postinstall(); echo $name." updated\n";
}
