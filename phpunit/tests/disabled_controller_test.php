<?php
/** The obsolete browser generator must never load services or dispatch writes. */
if (PHP_SAPI !== 'cli') exit(64);
$GLOBALS['kewl_entry_point_run'] = true;
class controller {
    public function __call($name, $args) {
        if (!in_array($name, ['setPageTemplate', 'setLayoutTemplate', 'setVar'], true)) {
            throw new RuntimeException('Unexpected generator dependency or action: '.$name);
        }
    }
}
require dirname(__DIR__).'/controller.php';
$c = new phpunit(); $c->init();
foreach (['GET', 'POST'] as $method) {
    $_SERVER['REQUEST_METHOD'] = $method;
    foreach (['', 'gentestcase', 'getjsondata', 'getform', 'edit', 'redirect', 'anything'] as $action) {
        $_GET = ['action'=>$action,'mod'=>'../../fixture','modpath'=>'/tmp'];
        $_POST = $_GET;
        http_response_code(200);
        if ($c->dispatch($action) !== 'unavailable_tpl.php' || http_response_code() !== 410) throw new RuntimeException('Old route still active');
    }
}
echo "PASS all old actions inert for GET/POST, without loading generator services\n";
