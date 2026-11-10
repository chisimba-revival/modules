<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class photostore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_photogallery_cache',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:null;}
    public function save(array $row,$exists=true){$id=$row['id'];unset($row['id']);$result=$exists?$this->update('id',$id,$row):$this->insert(['id'=>$id]+$row);if($result===false||PEAR::isError($result))throw new RuntimeException('storage');}
}
