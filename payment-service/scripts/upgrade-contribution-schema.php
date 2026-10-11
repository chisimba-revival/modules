<?php
/** Explicit, idempotent schema step for existing payment installations.
 * Run after a backup and module update; never backfills VAT on historical payments.
 * @author Derek Keats
 */
if(PHP_SAPI!=='cli'||!in_array($argv[1]??'',array('--check','--apply'),true))exit(1);
chdir(dirname(__DIR__,3));$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['SCRIPT_NAME']=$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();$changes=$e->getObject('paymentmaintenance','payment-service')->reconcile($argv[1]==='--apply');
foreach($changes as $sql)echo ($argv[1]==='--apply'?'Applied: ':'Required: ').$sql."\n";
if(!$changes)echo "Contribution schema already current.\n";
