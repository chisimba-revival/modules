<?php
// Browser fixture using the production templates; no application/database access.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$kanbanScope = array('type'=>'personal','id'=>'user','label'=>'Personal board');
$kanbanCsrf = 'initial-token';
$kanbanMessage = $kanbanError = '';
$kanbanCanCreate = false;
$task = array('id'=>str_repeat('c',32),'title'=>'Existing task','description'=>'Existing description','notes'=>'Keep these notes closed','status'=>'completed','subtasks'=>array());
$board = array('id'=>str_repeat('a',32),'title'=>'Meeting project','description'=>'Capture actions as we talk','scopetype'=>'personal','scopeid'=>'user','permission'=>'edit','tasks'=>array($task));
$other = array_merge($board,array('id'=>str_repeat('d',32),'title'=>'Other project','tasks'=>array()));
$kanbanBoards = array($board,$other);
$renderer = new class {
    public function uri($params, $module) { return '/index.php?module=kanban&'.http_build_query($params); }
    public function getObject($name,$module) { return new class {
        public function getContextCode() { return ''; }
        public function show(...$args) { return ''; }
        public function userId() { return 'user'; }
        public function languageText($key, $module) {
            foreach (file(dirname(__DIR__,2).'/register.conf') as $line) {
                $parts=explode('|',trim($line),3);
                if(($parts[0]??'')==='TEXT: '.$key)return $parts[2];
            }
            return $key;
        }
        public function isAdmin() { return false; }
        public function render($name,$options) { return ''; }
    }; }
    public function render($vars) {
        extract($vars);
        include dirname(__DIR__,2).'/templates/content/index_tpl.php';
    }
};
echo '<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="/kanban.css"></head><body>';
$renderer->render(get_defined_vars());
echo '<script src="/formdrafts.js"></script><script src="/kanban.js"></script></body></html>';
