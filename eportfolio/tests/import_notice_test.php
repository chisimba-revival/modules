<?php
$GLOBALS['kewl_entry_point_run']=true;
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
$renderer=new class {
 public $message;
 public $objLanguage;
 function __construct(){$this->objLanguage=new class {function languageText($k,$m){return '<unsafe> '.$k;}};}
 function getParam($key){return $this->message;}
 function render(){ob_start();include dirname(__DIR__).'/templates/content/import_notice.php';return ob_get_clean();}
};
foreach(['uploadsuccessful'=>'success','sorryemptypdf'=>'error'] as $message=>$style){$renderer->message=$message;$html=$renderer->render();if(!str_contains($html,'notice--'.$style)||str_contains($html,'<unsafe>')||!str_contains($html,'&lt;unsafe&gt;'))throw new RuntimeException('Notice missing or unsafe');}
foreach([null,[], 'unknown'] as $message){$renderer->message=$message;if($renderer->render()!=='')throw new RuntimeException('Unexpected notice');}
echo "PASS persistent import notices, escaping and invalid message handling\n";
