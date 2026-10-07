<?php
ob_start();
/** Isolated controller behaviour: no database, accounts or sessions are changed. */
class controller
{
    public array $objects = [], $vars = [], $params = [];
    public function getObject($name, $module) { return $this->objects[$name]; }
    public function setLayoutTemplate($name) {}
    public function setVar($key, $value) { $this->vars[$key] = $value; }
    public function getParam($key, $default = null) { return $this->params[$key] ?? $default; }
    public function getSession($key) { return 'test-token'; }
    public function setSession($key, $value) {}
}
require dirname(__DIR__) . '/controller.php';
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
foreach (['anonymous', 'member', 'editor', 'administrator'] as $role) {
    $page = new sitepages();
    $page->objects['user'] = new class($role) {
        public function __construct(private string $role) {}
        public function isAdmin() { return $this->role === 'administrator'; }
    };
    $page->objects['pagepolicy'] = new class($role) {
        public function __construct(private string $role) {}
        public function canManage() { return in_array($this->role, ['editor', 'administrator'], true); }
    };
    $db = new class {
        public array $reads = [];
        public function find($id) { return $id==='chosen' ? ['id'=>'chosen','title'=>'Chosen page','body_html'=>'Kept content'] : false; }
        public function findBySlug($slug, $published) {
            $this->reads[] = [$slug, $published];
            return ['slug'=>$slug, 'title'=>'Public home', 'status'=>'published', 'body_html'=>'Home content'];
        }
        public function activeRows() { return [['title'=>'Private draft']]; }
    };
    $page->objects['dbsitepages'] = $db;
    $page->objects['htmlcleaner'] = new class { public function cleanHtml($html) { return $html; } };
    $page->objects['language'] = new class { public function languageText($key, $module) { return $key; } };
    $page->objects['compositionservice'] = new class { public function fromPost($post) { return []; } };
    $page->init();
    foreach ([null, '', 'view'] as $action) {
        $page->vars = [];
        check(!$page->requiresLogin($action), 'Public route unexpectedly requires login');
        check($page->dispatch($action) === 'view_tpl.php', "$role default must show public page");
        check($page->vars['sitepagesPage']['title'] === 'Public home', 'Home not selected');
        check(!isset($page->vars['sitepagesRows']), 'Management rows leaked into home');
        check($page->vars['sitepagesCanEdit'] === in_array($role, ['editor', 'administrator'], true), 'Edit icon permission incorrect');
    }
    foreach ($db->reads as [$slug, $published]) {
        check($slug === 'home', 'Incorrect default slug');
        check($published === !in_array($role, ['editor', 'administrator'], true), 'Publication visibility changed');
    }
    check($page->requiresLogin('manage'), 'Management must require login');
    $page->vars = [];
    check($page->dispatch('manage') === 'manage_tpl.php', 'Explicit management unavailable');
    check(($page->vars['sitepagesRows'] !== []) === in_array($role, ['editor', 'administrator'], true), 'Management data access incorrect');
    if (!in_array($role, ['editor', 'administrator'], true)) check($page->vars['sitepagesDenied'] === true, 'Non-administrator not denied');
    echo "PASS: $role public entry, publication filter and explicit management access\n";
}

// The Ajax consumer rejects any response marked as an error. Normal unsaved
// block commands must remain distinguishable from expired/invalid submissions.
$recover = new ReflectionMethod(sitepages::class, 'recover');
foreach (['unsaved'=>false, 'expired'=>true, 'invalid'=>true, 'failed'=>true] as $message=>$isError) {
    $input = ['title'=>'Unsaved draft', 'blocks'=>[['id'=>'kept-block']]];
    $recover->invoke($page, $input, $message);
    check($page->vars['sitepagesError'] === $isError, 'Incorrect Ajax error status: '.$message);
    check($page->vars['sitepagesEdit'] === $input, 'Submitted draft lost: '.$message);
    check($page->vars['sitepagesBlocks'] === $input['blocks'], 'Submitted blocks lost: '.$message);
}
echo "PASS: unsaved block updates succeed; genuine errors retain submitted work\n";

$page->params=['id'=>'chosen'];$page->vars=[];
$page->dispatch('manage');
check($page->vars['sitepagesEditing']===true,'Edit must open the focused editor');
check($page->vars['sitepagesEdit']['id']==='chosen','Wrong page selected');
check($page->vars['sitepagesRows']===[],'Page list must not precede the editor');
$page->params=['new'=>'1'];$page->vars=[];$page->dispatch('manage');
check($page->vars['sitepagesEditing']===true&&$page->vars['sitepagesRows']===[],'New page must open a focused empty editor');
$page->params=[];$page->vars=[];$page->dispatch('manage');
check($page->vars['sitepagesEditing']===false,'Page index must not contain an empty editor');
$page->params=['id'=>'missing'];$page->vars=[];$page->dispatch('manage');
check(http_response_code()===404&&!empty($page->vars['sitepagesMissingEdit']),'Missing edit target must not create a new page');
echo "PASS: focused edit/new routes, separate index and missing-page handling\n";
