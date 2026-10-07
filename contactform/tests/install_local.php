<?php
/** Explicit local-only Catalogue install/update. Never use against a production database. */
if (PHP_SAPI !== 'cli' || getenv('CONTACT_TEST_SITE') !== '/var/www/html/ch') exit(64);
chdir(getenv('CONTACT_TEST_SITE'));
$config = simplexml_load_file('config/config.xml');
$host = parse_url((string)$config->KEWL_SITE_ROOT, PHP_URL_HOST);
if (!in_array($host, ['chisimba.test','localhost','127.0.0.1'], true)) throw new RuntimeException('Not a local development site');
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']=$host; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true; require 'classes/core/engine_class_inc.php'; $engine=new engine();
$f=$engine->getObject('modulefile','modulecatalogue'); $admin=$engine->getObject('modulesadmin','modulecatalogue'); $modules=$engine->getObject('modules','modulecatalogue');
foreach (['abuseprotection','contactform'] as $name) {
    $record=$f->readRegisterFile($f->findRegisterFile($name));
    if (!$admin->installModule($record,(bool)$modules->checkIfRegistered($name))) throw new RuntimeException('Installation failed: '.$name);
    if ($name==='contactform') $engine->getPatchObject($name)->postinstall();
    echo 'PASS: '.$name." installed through Module Catalogue\n";
}
