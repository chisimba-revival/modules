<?php
/** Add private validation reports without altering saved sets. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class questiongenerator_installscripts extends ChisimbaObject
{
 public function postinstall($version=null){
  // Transfer catalogue ownership only: never copy, truncate or rename saved tables.
  $db=$this->objEngine->getDbObj();
  $result=$db->exec("UPDATE tbl_modules_owned_tables SET kng_module='questiongenerator' WHERE kng_module IN ('mcqgenerator','questionworkshop') AND tablename IN ('tbl_questionworkshop_sets','tbl_mcqgenerator_exams')");
  if($result===false||PEAR::isError($result))throw new RuntimeException('Table ownership migration failed');
  $this->getObject('examstore','questiongenerator')->assignLegacySets();
 }
 public function preinstall($version=null){
  $admin=$this->getObject('modulesadmin','modulecatalogue');$table='tbl_questionworkshop_sets';
  $tables=$admin->listDbTables();if(!is_array($tables))throw new RuntimeException('Schema inspection failed');
  if(!in_array($table,$tables,true))return;
  $columns=$admin->listTblFields($table);if(!is_array($columns))throw new RuntimeException('Schema inspection failed');
  $add=[];foreach(['validation_json','generation_json'] as $column)if(!in_array($column,$columns,true))$add[$column]=['type'=>'clob'];
  if(!in_array('examid',$columns,true))$add['examid']=['type'=>'text','length'=>32,'default'=>'','notnull'=>true];
  if(!in_array('question_type',$columns,true))$add['question_type']=['type'=>'text','length'=>16,'default'=>'mcq','notnull'=>true];
  if(!$add)return;
  $changes=['add'=>$add];
  foreach([true,false] as $check){$result=$admin->alterTable($table,$changes,$check);if($result!==true&&$result!==MDB2_OK)throw new RuntimeException('Schema update failed');}
 }
}
