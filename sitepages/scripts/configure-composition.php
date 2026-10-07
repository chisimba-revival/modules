<?php
/** Explicit, repeatable page-composition upgrade. Run after Module Catalogue update. */
if(PHP_SAPI!=='cli')exit(1);
$root=$argv[1]??'';if(!$root||!is_file($root.'/classes/core/engine_class_inc.php'))throw new RuntimeException('Supply the intended application root');
chdir($root);$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']=$argv[2]??'localhost';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['PHP_SELF']='/ch/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();$db=$e->getDbObj();$r=$db->query('SHOW COLUMNS FROM tbl_sitepages');if(PEAR::isError($r))throw new RuntimeException('Install sitepages first');$columns=array_column($r->fetchAll(MDB2_FETCHMODE_ASSOC),'field');
if(!in_array('composition_json',$columns,true)){$r=$db->exec('ALTER TABLE tbl_sitepages ADD COLUMN composition_json LONGTEXT NULL');if(PEAR::isError($r))throw new RuntimeException('Page upgrade failed');}
$r=$db->exec('ALTER TABLE tbl_sitepages ENGINE=InnoDB');if(PEAR::isError($r))throw new RuntimeException('Transactional page upgrade failed');echo "Page composition schema ready; existing content preserved.\n";
