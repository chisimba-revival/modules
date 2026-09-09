<?php
/** Older Communications schema must not break the administration dashboard.
 * @author Derek Keats
 * @package myadmin
 */
$GLOBALS['kewl_entry_point_run']=true;
function log_debug($message) {}
class dbTable {
    public $tables=array('tbl_communications_outbox');
    public $heartbeat=null;
    public $heartbeatQueries=0;
    public function listDbTables(){return $this->tables;}
    public function getArray($sql){return str_contains($sql,'GROUP BY')?array(array('status'=>'queued','total'=>2),array('status'=>'sent','total'=>9)):array(array('last_sent'=>'2026-09-09 11:00:00'));}
    public function getRow($key,$value,$table){$this->heartbeatQueries++;if(!in_array($table,$this->tables,true))throw new RuntimeException('Missing-table query attempted');return $this->heartbeat;}
}
require dirname(__DIR__).'/classes/mailqueuehealth_class_inc.php';
$health=new mailqueuehealth();$result=$health->summary();
if($result['timer']!=='upgrade'||$result['queued']!==2||$result['sent']!==9||$health->heartbeatQueries!==0)throw new RuntimeException('Legacy schema not handled safely');
$health->tables[]='tbl_communications_worker_state';
if($health->summary()['timer']!=='unknown')throw new RuntimeException('Empty heartbeat should be unknown');
$health->heartbeat=array('last_run_at'=>date('Y-m-d H:i:s'));
if($health->summary()['timer']!=='running')throw new RuntimeException('Recent heartbeat not running');
$health->heartbeat=array('last_run_at'=>date('Y-m-d H:i:s',time()-600));
if($health->summary()['timer']!=='stalled')throw new RuntimeException('Old heartbeat not stalled');
echo "PASS: missing, empty, recent and stale monitoring state handled without querying missing tables\n";
