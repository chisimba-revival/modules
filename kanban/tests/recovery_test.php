<?php
/** Run real controller rejection paths without a database or user session. */
if (PHP_SAPI !== 'cli') exit;
if ($argc === 1) {
    foreach (['renew'=>200,'cross-site'=>403,'wrong-actor'=>403,'get'=>403,'edit-expired'=>403,'edit-actor'=>403,'board-expired'=>422] as $case=>$status) {
        $result=json_decode(shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($case)),true);
        if (!$result || $result['status']!==$status || $result['ok']!==($case==='renew')) throw new RuntimeException('Unexpected result: '.$case);
        echo "PASS: $case\n";
    }
    exit;
}
$GLOBALS['kewl_entry_point_run']=true;
$case=$argv[1];
$_SERVER['REQUEST_METHOD']=$case==='get'?'GET':'POST';
$_SERVER['HTTP_X_CHISIMBA_FORM']='kanban';
$_SERVER['HTTP_SEC_FETCH_SITE']=$case==='cross-site'?'cross-site':'same-origin';
#[AllowDynamicProperties]
class controller {
    public function getParam($name,$default='') {
        global $case;
        return ['response'=>'json','actor'=>in_array($case,['wrong-actor','edit-actor'])?'another-user':'user','csrf_token'=>'stale-token'][$name]??$default;
    }
}
require dirname(__DIR__).'/controller.php';
$controller=new kanban();
$controller->user=new class {public function userId(){return 'user';}};
$controller->csrf=new class {
    public function consume($context,$token){return false;}
    public function issueForSession($context){return 'new-token';}
};
// Any attempt to access an uninitialised repository fails: rejected requests must never write.
ob_start(function($out){$data=json_decode($out,true);$data['status']=http_response_code();return json_encode($data);});
$controller->dispatch(str_starts_with($case,'edit-')?'savetask':($case==='board-expired'?'saveproject':'formtoken'));
