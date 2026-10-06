<?php
// Browser fixture using the production templates; no application/database access.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if(getenv('KANBAN_FIXTURE_HELP')) {
    class ChisimbaObject {
        public function getObject($name,$module=null) { return $GLOBALS['renderer']->getObject($name,$module); }
        public function getJavaScriptFile($file,$module) { return '<script defer src="/contextualhelp.js"></script>'; }
    }
    require dirname(__DIR__,4).'/framework/app/core_modules/ui/classes/iconservice_class_inc.php';
    require dirname(__DIR__,4).'/framework/app/core_modules/help/classes/contextualhelp_class_inc.php';
    require dirname(__DIR__,2).'/classes/helpcontent_class_inc.php';
}
$kanbanScope = array('type'=>'personal','id'=>'user','label'=>'Personal board');
$kanbanCsrf = 'initial-token';
$kanbanMessage = $kanbanError = '';
$kanbanCanCreate = (bool)getenv('KANBAN_FIXTURE_HELP');
$task = array('id'=>str_repeat('c',32),'title'=>'Existing task','description'=>'Existing description','notes'=>'Keep these notes closed','status'=>'completed','subtasks'=>array());
if(getenv('KANBAN_FIXTURE_SUBTASKS'))$task['subtasks']=array(array('id'=>str_repeat('e',32),'title'=>'First subtask','iscompleted'=>1),array('id'=>str_repeat('f',32),'title'=>'Second subtask','iscompleted'=>0));
$board = array('id'=>str_repeat('a',32),'title'=>'Meeting project','description'=>'Capture actions as we talk','scopetype'=>'personal','scopeid'=>'user','permission'=>getenv('KANBAN_FIXTURE_VIEW')?'view':(getenv('KANBAN_FIXTURE_MANAGE')?'manage':'edit'),'tasks'=>array($task),'linkednotes'=>array(),'availablenotes'=>array());
$other = array_merge($board,array('id'=>str_repeat('d',32),'title'=>'Other project','tasks'=>array()));
$kanbanBoards = array($board,$other);
$renderer = new class {
    public function uri($params, $module) { return '/index.php?module=kanban&'.http_build_query($params); }
    public function getObject($name,$module=null) {
        if(getenv('KANBAN_FIXTURE_HELP')) {
            if($name==='iconservice')return new iconservice();
            if($name==='helpcontent')return new helpcontent();
            if($name==='contextualhelp'){$help=new contextualhelp();$help->init();return $help;}
        }
        return new class {
        public function getContextCode() { return ''; }
        public function show(...$args) { return ''; }
        public function setId($value) { return $this; }
        public function setTitle($value) { return $this; }
        public function setWidth($value) { return $this; }
        public function setContent($value) { return $this; }
        public function userId() { return 'user'; }
        public function isLoggedIn() { return true; }
        public function languageText($key, $module) {
            $register=$module==='help'?dirname(__DIR__,4).'/framework/app/core_modules/help/register.conf':dirname(__DIR__,2).'/register.conf';
            foreach (file($register) as $line) {
                $parts=explode('|',trim($line),3);
                if(($parts[0]??'')==='TEXT: '.$key)return htmlentities($parts[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
            }
            return $key;
        }
        public function isAdmin() { return false; }
        public function render($name,$options) { return ''; }
        public function grants($boardId) { return array(); }
        public function publicLinkToken($boardId) { return false; }
    }; }
    public function render($vars) {
        extract($vars);
        include dirname(__DIR__,2).'/templates/content/index_tpl.php';
    }
};
// Production loads module CSS before the skin; reversing this hid real cascade bugs.
echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
if(getenv('KANBAN_FIXTURE_HELP'))echo '<link rel="stylesheet" href="/ui.css">';
echo '<link rel="stylesheet" href="/kanban.css"><link rel="stylesheet" href="/skin.css">';
if(getenv('KANBAN_FIXTURE_HELP'))echo '<link rel="stylesheet" href="/canvas-default.css"><link rel="stylesheet" href="/canvas-kenga.css">';
echo '</head><body>';
$renderer->render(get_defined_vars());
echo '<script src="/formdrafts.js"></script><script src="/kanban.js"></script></body></html>';
