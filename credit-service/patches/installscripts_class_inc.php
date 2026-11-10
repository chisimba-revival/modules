<?php
/** Chisimba reserves the primary key for puid: credit IDs need explicit uniqueness. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class credit_service_installscripts extends dbTable
{
    public function postinstall($version=null)
    {
        foreach(['accounts','entries','holds'] as $suffix){
            $table='tbl_credit_'.$suffix;$unique=false;
            foreach($this->selectExactPrepared('SHOW INDEX FROM '.$table) as $row){
                $row=array_change_key_case($row,CASE_LOWER);
                if($row['column_name']==='id'&&(int)$row['non_unique']===0)$unique=true;
            }
            if(!$unique&&$this->getObject('modulesadmin','modulecatalogue')->executeSchemaStatement('ALTER TABLE '.$table.' ADD UNIQUE KEY credit_id_unique (id)')===false)throw new RuntimeException('Credit unique index installation failed');
        }
    }
}
class_alias(credit_service_installscripts::class,'credit-service_installscripts');
