<?php
/** Explicit, idempotent schema step for existing payment installations.
 * Run after a backup and module update; never backfills VAT on historical payments.
 * @author Derek Keats
 */
if(PHP_SAPI!=='cli'||!in_array($argv[1]??'',array('--check','--apply'),true))exit(1);
chdir(dirname(__DIR__,3));$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['SCRIPT_NAME']=$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();$db=$e->getDbObj();
$read=static function($sql)use($db){$r=$db->query($sql);if(PEAR::isError($r))throw new RuntimeException('Payment schema read failed');return $r->fetchAll(MDB2_FETCHMODE_ASSOC);};
$changes=array();
$columns=$read('SHOW COLUMNS FROM tbl_payment_service_intents');$found=false;foreach($columns as $column)if($column['field']==='user_id'){$found=true;if($column['null']!=='YES')$changes[]='ALTER TABLE tbl_payment_service_intents MODIFY user_id VARCHAR(25) NULL';}if(!$found)throw new RuntimeException('Missing payment identity column');
$columns=$read('SHOW COLUMNS FROM tbl_payment_service_prices');$found=false;foreach($columns as $column)if($column['field']==='vat_minor')$found=true;if(!$found)$changes[]='ALTER TABLE tbl_payment_service_prices ADD vat_minor INT NOT NULL DEFAULT 0';
foreach($changes as $sql){if($argv[1]==='--apply'){$r=$db->exec($sql);if(PEAR::isError($r))throw new RuntimeException('Payment schema upgrade failed');}echo ($argv[1]==='--apply'?'Applied: ':'Required: ').$sql."\n";}
if(!$changes)echo "Contribution schema already current.\n";
