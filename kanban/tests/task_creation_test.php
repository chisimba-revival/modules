<?php
/** Exercise task creation responses, permissions and escaped card rendering. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if ($argc === 1) {
    foreach (array('success'=>200, 'csrf'=>403, 'permission'=>403, 'title'=>422, 'save'=>500, 'wrongboard'=>403, 'oversize'=>422) as $case=>$status) {
        $output = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($case));
        $response = json_decode($output, true);
        if (!is_array($response) || $response['status'] !== $status || $response['ok'] !== ($case === 'success') || $response['csrfToken'] !== 'fresh-token') {
            throw new RuntimeException('Unexpected '.$case.' response: '.$output);
        }
        if ($case === 'success' && (!str_contains($response['taskHtml'], '&lt;script&gt;') || str_contains($response['taskHtml'], '<script>') || !str_contains($response['taskHtml'], 'data-task-move'))) {
            throw new RuntimeException('New card must escape input and include working task actions');
        }
        echo 'PASS: '.$case.PHP_EOL;
    }
    exit;
}
$GLOBALS['kewl_entry_point_run'] = true;
$_SERVER['REQUEST_METHOD'] = 'POST';
$case = $argv[1];
#[AllowDynamicProperties]
class controller {
    public function getParam($name, $default='') {
        global $case;
        return array('response'=>'json', 'csrf_token'=>'old-token', 'scope'=>'personal', 'boardid'=>str_repeat('a',32), 'taskid'=>$case==='wrongboard'?str_repeat('c',32):'', 'title'=>$case==='title'?'':($case==='oversize'?str_repeat('x',256):'<script>alert(1)</script>'), 'description'=>'<img src=x onerror=alert(1)>', 'notes'=>'Meeting notes')[$name] ?? $default;
    }
    public function uri($params, $module) { return 'index.php?module=kanban&'.http_build_query($params); }
}
require dirname(__DIR__).'/controller.php';
$controller = new kanban();
$controller->csrf = new class {
    public function consume($context,$token) { return $GLOBALS['case']!=='csrf'; }
    public function issueForSession($context) { return 'fresh-token'; }
};
$controller->context = new class { public function getContextCode() { return ''; } };
$controller->user = new class { public function userId() { return 'user'; } };
$controller->service = new class {
    public function board($id,$permission) { return $GLOBALS['case']==='permission'?false:array('id'=>$id); }
};
$controller->tasks = new class {
    public $task;
    public function createTask($data) {
        if (in_array($GLOBALS['case'],array('csrf','permission','title'),true)) throw new RuntimeException('Unauthorized insert');
        $this->task = array_merge($data,array('id'=>str_repeat('b',32)));
        return $GLOBALS['case']==='save'?false:$this->task['id'];
    }
    public function one($id) { return $GLOBALS['case']==='wrongboard'?array('boardid'=>'other'):$this->task; }
    public function saveTask($id,$data) { throw new RuntimeException('Unauthorized update'); }
};
$controller->subtasks = new class { public function forTask($id) { return array(); } };
// Capture the real JSON response, including the HTTP status set before exit.
ob_start(function($output) { $response=json_decode($output,true);$response['status']=http_response_code();return json_encode($response); });
$controller->dispatch('savetask');
