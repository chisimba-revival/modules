<?php
/** Pure consumer, filter and route tests; no database or external requests. */
if (PHP_SAPI !== 'cli') exit(64);
error_reporting(E_ALL); set_error_handler(static function($n,$m,$f,$l){throw new ErrorException($m,0,$n,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public static $registered=true; public $headers=[];
    public function getObject($name,$module='') {
        if($name==='contentmediaservice')return new contentmediaservice();
        if($name==='nativeembed')return $GLOBALS['native'];
        if($name==='modules')return new class {function checkIfRegistered(...$args){return ChisimbaObject::$registered;}};
        if($name==='language')return new class {function languageText($key,$module='',$default=false){return $default ?: $key;}};
        if($name==='user')return new stdClass();
        throw new RuntimeException('Unexpected service '.$name);
    }
    public function getResourceUri($path,$module){return '/packages/'.$module.'/resources/'.$path;}
    public function uri($args,$module){return '/index.php?module='.$module.'&action='.$args['action'];}
    public function appendArrayVar($key,$value){$this->headers[]=$value;}
}
class controller extends ChisimbaObject {
    public $action;public $vars=[];
    public function getParam($name,$default=null){return $name==='action'?$this->action:$default;}
    public function setVar($name,$value){$this->vars[$name]=$value;}
    public function setPageTemplate($value){}
    public function setLayoutTemplate($value){}
}
function check($value,$message){if(!$value)throw new RuntimeException($message);}
require dirname(__DIR__,2).'/contentblocks/classes/contentmediaservice_class_inc.php';
require dirname(__DIR__).'/classes/nativeembed_class_inc.php';
require dirname(__DIR__).'/controller.php';
$n=$GLOBALS['native']=new nativeembed();
foreach([null,[],123,'javascript:alert(1)','data:text/html,bad','//example.test/a.png','file:///etc/passwd','https://name:pass@youtube.com/watch?v=dQw4w9WgXcQ',"https://example.test/\nimage.png",'https://youtube.com/watch?v[]=bad','https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ','https://example.test/page','https://youtube.com/watch?'.str_repeat('a&',1001),'https://youtube.com/watch?v'.str_repeat('[x]',70).'=bad',str_repeat('x',5000)] as $bad)check($n->describe($bad)===null,'Unsafe/unsupported URL accepted');
foreach([
 'https://youtu.be/dQw4w9WgXcQ'=>['frame','https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
 'https://vimeo.com/123456'=>['frame','https://player.vimeo.com/video/123456'],
 'https://xeno-canto.org/395736'=>['frame','https://xeno-canto.org/395736/embed'],
 'https://example.test/a.png'=>['image','https://example.test/a.png'],
 'https://example.test/a.mp3'=>['audio','https://example.test/a.mp3'],
 'https://example.test/a.webm'=>['video','https://example.test/a.webm']
] as $url=>$expected)check($n->describe($url)===['kind'=>$expected[0],'url'=>$expected[1]],'Supported media lost');
check($n->describe('http://example.test/a.mp3')===null,'Mixed content');
check(!str_contains($n->link('javascript:alert(1)'),'<a'),'Unsafe link');
$html=$n->link('https://example.test/a.png?x="/><script>alert(1)</script>');
check(!str_contains($html,'<script>')&&str_contains($html,'&quot;'),'Link escaping');
$n->load();$count=count($n->headers);$n->load();check(count($n->headers)===$count,'Duplicate loader');
check(!str_contains(implode('',$n->headers),'jquery'),'Legacy loader');
$c=new oembed();$c->init();
foreach(['GET','POST'] as $method){$_SERVER['REQUEST_METHOD']=$method;foreach(['provide','provideimage','presentation','__provide','getMethod','unknown'] as $action){$c->action=$action;http_response_code(200);check($c->dispatch()==='unavailable_tpl.php'&&http_response_code()===410,'Legacy publisher active');}}
$c->action='resolve';$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);check($c->dispatch()==='resolve_tpl.php'&&http_response_code()===405,'Resolver method');
$_SERVER['REQUEST_METHOD']='POST';$_POST=['url'=>'https://example.test/a.mp3'];http_response_code(200);$c->dispatch();check($c->vars['media']['kind']==='audio','Resolver output');
$framework=getenv('CHISIMBA_FRAMEWORK_ROOT') ?: dirname(__DIR__,3).'/framework/app';
require $framework.'/core_modules/filters/classes/parse4quickembed_class_inc.php';
$f=new parse4quickembed();$input='[quickembed:https://example.test/a.png]';check(str_contains($f->parse($input),'data-native-embed'),'Filter missing');
check(!str_contains($f->parse('[quickembed:javascript:alert(1)]'),'href='),'Filter scheme');
ChisimbaObject::$registered=false;check($f->parse($input)===$input,'Unregistered filter changed');
check(is_file(dirname(__DIR__).'/resources/nativeembed.js'),'Native asset missing');
echo "PASS native media recognition, hostile URLs, escaped links, loader deduplication, filter gating and closed publishing routes\n";
