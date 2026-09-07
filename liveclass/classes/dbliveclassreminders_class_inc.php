<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class dbliveclassreminders extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_liveclass_reminders',$pearDb,$errorCallback);}
 public function schedule($sessionId,$lead,$due){$now=date('Y-m-d H:i:s');return $this->insert(array('id'=>bin2hex(random_bytes(16)),'session_id'=>$sessionId,'lead_minutes'=>(int)$lead,'due_at'=>$due,'status'=>'pending','recipient_count'=>0,'created_at'=>$now,'updated_at'=>$now));}
 public function due($limit=20){$limit=max(1,min(100,(int)$limit));$now=date('Y-m-d H:i:s');$rows=$this->getArray("SELECT * FROM tbl_liveclass_reminders WHERE status='pending' AND due_at <= '".$now."' ORDER BY due_at ASC LIMIT ".$limit);return is_array($rows)?$rows:array();}
 public function forSession($sessionId){$rows=$this->getAll("WHERE session_id=".$this->quote($sessionId)." ORDER BY lead_minutes DESC");return is_array($rows)?$rows:array();}
 public function complete($id,$count){return $this->update('id',$id,array('status'=>'queued','recipient_count'=>(int)$count,'queued_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')));}
 public function cancelSession($sessionId){$rows=$this->forSession($sessionId);foreach($rows as $row)if($row['status']==='pending')$this->update('id',$row['id'],array('status'=>'cancelled','updated_at'=>date('Y-m-d H:i:s')));}
 private function quote($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
