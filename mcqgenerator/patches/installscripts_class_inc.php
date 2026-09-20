<?php
/** Add private validation reports without altering saved sets. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class mcqgenerator_installscripts extends ChisimbaObject
{
 public function postinstall($version=null){$this->getObject('examstore','mcqgenerator')->assignLegacySets();}
 public function preinstall($version=null){
  $admin=$this->getObject('modulesadmin','modulecatalogue');$table='tbl_questionworkshop_sets';
  $tables=$admin->listDbTables();if(!is_array($tables))throw new RuntimeException('Schema inspection failed');
  if(!in_array($table,$tables,true))return;
  $columns=$admin->listTblFields($table);if(!is_array($columns))throw new RuntimeException('Schema inspection failed');
  $add=[];foreach(['validation_json','generation_json'] as $column)if(!in_array($column,$columns,true))$add[$column]=['type'=>'clob'];
  if(!in_array('examid',$columns,true))$add['examid']=['type'=>'text','length'=>32,'default'=>'','notnull'=>true];
  if(!$add)return;
  $changes=['add'=>$add];
  foreach([true,false] as $check){$result=$admin->alterTable($table,$changes,$check);if($result!==true&&$result!==MDB2_OK)throw new RuntimeException('Schema update failed');}
 }
}
