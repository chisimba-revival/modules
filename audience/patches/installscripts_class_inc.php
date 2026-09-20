<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audience_installscripts extends ChisimbaObject {
 public function init(){}
 public function postinstall($version=null){$s=$this->getObject('audienceadmin','audience');foreach(['tbl_audience_campaigns'=>['id'],'tbl_audience_contacts'=>['id','email'],'tbl_communications_outbox'=>['id','idempotency_key']] as $table=>$columns){$s->execute('ALTER TABLE '.$table.' ENGINE=InnoDB');$indexes=$s->rows('SHOW INDEX FROM '.$table);foreach($columns as $column){$found=false;$groups=[];foreach($indexes as $i)if(!$i['non_unique'])$groups[$i['key_name']][]=$i['column_name'];foreach($groups as $g)if($g===[$column])$found=true;if(!$found)$s->execute('ALTER TABLE '.$table.' ADD UNIQUE KEY audience_'.$column.'_unique ('.$column.')');}}}
}
