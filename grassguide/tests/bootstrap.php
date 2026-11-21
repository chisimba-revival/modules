<?php
/** Isolated test composition; real dbTable, schema, CSRF, icons and Help. Never include in a site. */
if (getenv('GRASSGUIDE_TEST') !== '1' || !getenv('GRASSGUIDE_TEST_PASSWORD')) exit(64);
$GLOBALS['kewl_entry_point_run']=true;
$app='/framework/app'; set_include_path($app.PATH_SEPARATOR.get_include_path());
class ChisimbaObject
{
    public function getObject($class,$module=null) { return $GLOBALS['objects'][$class]; }
    public function getParam($key,$default=null) { return $_POST[$key] ?? $_GET[$key] ?? $default; }
    public function uri($params=[],$module='grassguide') { return '/?'.http_build_query(['module'=>$module]+$params); }
    public function getJavaScriptFile($file,$module) { return '<script src="/assets/'.$module.'/'.$file.'" defer></script>'; }
}
class controller extends ChisimbaObject
{
    public array $vars=[];
    public function setVar($key,$value) { $this->vars[$key]=$value; }
    public function nextAction($action,$params=[]) { header('Location: '.$this->uri(['action'=>$action]+$params)); return null; }
    public function render($template) { extract($this->vars); include dirname(__DIR__).'/templates/content/'.$template; }
}
require_once $app.'/classes/core/dbtable_class_inc.php';
require_once $app.'/classes/core/chisimbadatabaseschema.php';
require_once $app.'/core_modules/security/classes/nativeauth/csrftokenservice.php';
require_once $app.'/core_modules/ui/classes/iconservice_class_inc.php';
require_once $app.'/core_modules/help/classes/contextualhelp_class_inc.php';
foreach (glob(dirname(__DIR__).'/classes/*.php') as $file) require_once $file;
require_once dirname(__DIR__).'/controller.php';
$db=ChisimbaDatabaseConnection::fromLegacyDsn(['phptype'=>'mysqli','hostspec'=>'grassguide-db:3306','username'=>'root','password'=>getenv('GRASSGUIDE_TEST_PASSWORD'),'database'=>'grassguide_test']);
$store=new grassguidestore();
(new ReflectionProperty(dbTable::class,'_db'))->setValue($store,$db);
$store->dbLayer='PDO'; $store->dbType='mysqli'; $store->_tableName='tbl_grassguide_species'; $store->_serverName='fixture';
$store->executeExactPrepared('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
$user=new class {
    public bool $admin=true;
    public bool $logged=true;
    public function isLoggedIn(){return $this->logged;}
    public function isAdmin(){return $this->admin;}
    public function userId(){return 'fixture-admin';}
};
$language=new class {
    public array $strings=[];
    public function code2Txt($key,$module=null){return $this->strings[$key] ?? $key;}
    public function languageText($key,$module=null){return $this->code2Txt($key,$module);}
};
foreach ([dirname(__DIR__).'/register.conf',$app.'/core_modules/help/register.conf'] as $file) foreach (file($file) as $line) if (str_starts_with($line,'TEXT: ')) {
    $parts=explode('|',trim(substr($line,6)),3); if(count($parts)===3)$language->strings[$parts[0]]=$parts[2];
}
$backend=new class {
    public function getSession($key,$default=null){return $_SESSION[$key] ?? $default;}
    public function setSession($key,$value){$_SESSION[$key]=$value;}
    public function unsetSession($key){unset($_SESSION[$key]);}
};
$csrf=new CsrfTokenService($backend,900,null,fn()=>$user->logged?'fixture-session':null);
$GLOBALS['objects']=['user'=>$user,'language'=>$language,'grassguiderights'=>new grassguiderights(),'grassguidestore'=>$store,'grassguideproviders'=>new grassguideproviders(),'grassguideinput'=>new grassguideinput(),'grassguideservice'=>new grassguideservice(),'grassguiderenderer'=>new grassguiderenderer(),'iconservice'=>new iconservice(),'helpcontent'=>new helpcontent(),'contextualhelp'=>new contextualhelp(),'nativeauthwebcomposition'=>new class($csrf) {
    public function __construct(private $csrf){}
    public function build(){return ['csrf'=>$this->csrf];}
}];
$GLOBALS['objects']['contextualhelp']->init();
$service=$GLOBALS['objects']['grassguideservice']; $service->init();
function ggCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
function ggReject($operation,$expected){try{$operation();}catch(DomainException $e){ggCheck($e->getMessage()===$expected,'Unexpected error: '.$e->getMessage());return;}throw new RuntimeException('Accepted invalid operation: '.$expected);}
