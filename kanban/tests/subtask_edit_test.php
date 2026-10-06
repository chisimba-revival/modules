<?php
/** Real controller and repository contracts; synthetic dependencies only. */
if(PHP_SAPI!=='cli')exit(64);
if($argc===1){
    foreach(array('success'=>200,'csrf'=>403,'get'=>403,'actor'=>403,'denied'=>403,'missing'=>403,'blank'=>422,'long'=>422,'conflict'=>409,'storage'=>500) as $case=>$status){
        $response=json_decode(shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$case),true);
        if(!is_array($response)||$response['status']!==$status||$response['ok']!==($case==='success')||$response['csrfToken']!=='renewed')throw new RuntimeException('Failed '.$case.': '.json_encode($response));
        if($case==='success'&&$response['subtask']['title']!=='<b>New title</b>')throw new RuntimeException('Title changed unexpectedly');
        echo 'PASS: controller '.$case.PHP_EOL;
    }
    $GLOBALS['kewl_entry_point_run']=true;
    class dbTable {
        public $row=array('title'=>'Old title','iscompleted'=>1,'sortorder'=>7),$queries=array(),$fail=false,$objEngine;
        public function query($sql){$this->queries[]=$sql;return true;}
        public function getArray($sql){if(!str_ends_with($sql,'FOR UPDATE'))throw new RuntimeException('Missing row lock');return array($this->row);}
        public function update($key,$id,$data){if(array_keys($data)!==array('title','datemodified'))throw new RuntimeException('Unexpected fields');if($this->fail)return false;$this->row=array_merge($this->row,$data);return true;}
    }
    require dirname(__DIR__).'/classes/dbkanbansubtasks_class_inc.php';
    $repository=new dbkanbansubtasks();
    $repository->objEngine=new class{public function getDbObj(){return new class{public function quoteSmart($v){return "'".str_replace("'","''",$v)."'";}};}};
    if($repository->renameSubtask('id','Old title','New title')!=='saved'||$repository->row['iscompleted']!==1||$repository->row['sortorder']!==7)throw new RuntimeException('Rename changed other data');
    if($repository->renameSubtask('id','Old title','Stale title')!=='conflict'||$repository->row['title']!=='New title')throw new RuntimeException('Stale overwrite');
    $repository->fail=true;
    if($repository->renameSubtask('id','New title','Failed')!=='error'||end($repository->queries)!=='ROLLBACK')throw new RuntimeException('Missing rollback');
    echo "PASS: row lock, stale-write rejection, completion/order preservation and rollback\n";
    exit;
}
$case=$argv[1];$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']=$case==='get'?'GET':'POST';
#[AllowDynamicProperties]
class controller {
    public function getParam($name,$default=''){
        global $case;
        return array('response'=>'json','actor'=>$case==='actor'?'other':'user','csrf_token'=>'token','subtaskid'=>str_repeat('e',32),'boardid'=>'forged-board','original_title'=>'Old title','title'=>$case==='blank'?' ':($case==='long'?str_repeat('a',256):'<b>New title</b>'))[$name]??$default;
    }
    public function getObject($name,$module=null){return new class{public function languageText($key,$module){return $key;}};}
}
require dirname(__DIR__).'/controller.php';
$app=new kanban();
$app->user=new class{public function userId(){return 'user';}};
$app->csrf=new class{public function consume($scope,$token){return $GLOBALS['case']!=='csrf';}public function issueForSession($scope){return 'renewed';}};
$app->subtasks=new class{
    public function one($id){return $GLOBALS['case']==='missing'?false:array('id'=>$id,'taskid'=>'actual-task');}
    public function renameSubtask($id,$expected,$title){if(!in_array($GLOBALS['case'],array('success','conflict','storage'),true))throw new RuntimeException('Unauthorised write');if($expected!=='Old title')throw new RuntimeException('Missing comparison');return match($GLOBALS['case']){'conflict'=>'conflict','storage'=>'error',default=>'saved'};}
};
$app->tasks=new class{public function one($id){if($id!=='actual-task')throw new RuntimeException('Untrusted parent');return array('id'=>$id,'boardid'=>'actual-board');}};
$app->service=new class{public function board($id,$permission){if($id!=='actual-board'||$permission!=='edit')throw new RuntimeException('Untrusted scope');return $GLOBALS['case']==='denied'?false:array('id'=>$id);}};
ob_start(function($output){$data=json_decode($output,true);$data['status']=http_response_code();return json_encode($data);});
$app->dispatch('updatesubtask');
