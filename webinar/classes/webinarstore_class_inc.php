<?php
/** Archive persistence. Source imports never overwrite an existing changed record. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarstore extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_webinar_records',$pearDb,$errorCallback);}
 public function published($kind){if(!in_array($kind,['webinar','speaker'],true))return [];return $this->getAll("WHERE status='published' AND kind='".$kind."' ORDER BY presented_at DESC,title ASC")?:[];}
 public function one($id){if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))return null;$r=$this->getRow('id',$id);return is_array($r)&&$r['status']==='published'?$r:null;}
 public function importRecord(array $r){$old=$this->getRow('source_key',$r['source_key']);if($old){if($old['source_hash']!==$r['source_hash'])throw new RuntimeException('Source changed: review before replacing '.$r['source_key']);return 'unchanged';}if($this->insert($r)===false)throw new RuntimeException('Import failed');return 'created';}
}
