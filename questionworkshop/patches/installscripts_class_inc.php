<?php
/** Add private validation reports without altering saved sets. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class questionworkshop_installscripts extends ChisimbaObject
{
 public function preinstall($version=null){
  $admin=$this->getObject('modulesadmin','modulecatalogue');$table='tbl_questionworkshop_sets';
  $tables=$admin->listDbTables();if(!is_array($tables))throw new RuntimeException('Schema inspection failed');
  if(!in_array($table,$tables,true))return;
  $columns=$admin->listTblFields($table);if(!is_array($columns))throw new RuntimeException('Schema inspection failed');
  if(in_array('validation_json',$columns,true))return;
  $changes=['add'=>['validation_json'=>['type'=>'clob']]];
  foreach([true,false] as $check){$result=$admin->alterTable($table,$changes,$check);if($result!==true&&$result!==MDB2_OK)throw new RuntimeException('Schema update failed');}
 }
}
