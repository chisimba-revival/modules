<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
/** Read mail status without querying monitoring tables absent on older installations. @author Derek Keats */
class mailqueuehealth extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init($tableName?:'tbl_communications_outbox',$pearDb,$errorCallback);}
 /** Check metadata first: MDB2 can terminate before a query error reaches a catch block. */
 public function workerMonitoringAvailable(){return in_array('tbl_communications_worker_state',(array)$this->listDbTables(),true);}
 public function summary(){
  $row=array('queued'=>0,'sent'=>0,'failed'=>0,'last_sent'=>null,'last_run'=>null,'timer'=>'unknown');
  try{$counts=$this->getArray("SELECT status,COUNT(*) AS total FROM tbl_communications_outbox WHERE channel='email' GROUP BY status");foreach((array)$counts as $count)$row[$count['status']]=(int)$count['total'];$last=$this->getArray("SELECT MAX(sent_at) AS last_sent FROM tbl_communications_outbox WHERE channel='email' AND status='sent'");if(isset($last[0]['last_sent']))$row['last_sent']=$last[0]['last_sent'];if(!$this->workerMonitoringAvailable()){$row['timer']='upgrade';return $row;}$heartbeat=$this->getRow('id','mail','tbl_communications_worker_state');if(is_array($heartbeat)&&!empty($heartbeat['last_run_at'])){$row['last_run']=$heartbeat['last_run_at'];$age=time()-strtotime($heartbeat['last_run_at']);$row['timer']=$age<=180?'running':'stalled';}}
  catch(Throwable $error){log_debug('My Administration mail queue health unavailable: '.$error->getMessage());}
  return $row;
 }
}
?>
