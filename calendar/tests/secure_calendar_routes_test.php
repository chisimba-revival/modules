<?php
/** Controller boundaries with synthetic identity, CSRF and repository adapters. */
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {}
class controller {
    public array $params=[],$vars=[],$session=[];
    public $editor;
    function getObject($name,$module=''){if($name==='calendareditor')return $this->editor;throw new LogicException('Unexpected service: '.$name);}
    function getParam($name,$default=null){return $this->params[$name]??$default;}
    function getSession($name,$default=null){return $this->session[$name]??$default;}
    function setSession($name,$value){$this->session[$name]=$value;}
    function setVar($name,$value){$this->vars[$name]=$value;}
    function setLayoutTemplate($name){}
    function nextAction($action,$params=[]){return 'redirect';}
}
require dirname(__DIR__).'/controller.php';
require dirname(__DIR__).'/classes/attachments_class_inc.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$c=new calendar();$c->contextCode='qa-course';$c->isInContext=true;
$c->objUser=new class {public bool $logged=true;function isLoggedIn(){return $this->logged;}function userId(){return 'qa-owner';}};
$c->editor=new class {
    public int $writes=0;public string $error='';
    function load($id){if($id!=='qa-event')throw new RuntimeException('calendar_denied');return ['id'=>$id,'revision'=>'original','title'=>'Original title'];}
    function csrf(){return new class{function issue($purpose){return 'fresh-token';}};}
    function mutate($action,$input,$method,$createId){++$this->writes;if($this->error)throw new RuntimeException($this->error);return $input['id']?:$createId;}
};
foreach(['GET','POST'] as $method){$_SERVER['REQUEST_METHOD']=$method;foreach(['tempframe','uploadattachment','downloadattachment','deleteattachment'] as $action){check($c->dispatch($action)==='security_notice_tpl.php'&&http_response_code()===410,'attachment route closed');}}
$_SERVER['REQUEST_METHOD']='GET';$c->params=['id'=>'qa-event'];check($c->dispatch('delete')==='addedit_event_tpl.php'&&$c->editor->writes===0&&$c->vars['deleteMode'],'GET delete only confirms');
check($c->dispatch('updateevent')==='security_notice_tpl.php'&&http_response_code()===405&&$c->editor->writes===0,'GET update rejected');
check($c->dispatch('saveevent')==='security_notice_tpl.php'&&http_response_code()===405,'GET save rejected');
$c->params=['id'=>'outsider'];check($c->dispatch('edit')==='security_notice_tpl.php'&&http_response_code()===403,'denied read');
$c->params=[];$c->dispatch('add');$creation=$c->vars['draft']['creationid'];check(strlen($creation)===32&&$c->session['calendarCreationIds'][$creation]==='qa-owner','server-issued creation ticket');
$_SERVER['REQUEST_METHOD']='POST';$c->params=['creationid'=>'forged'];$before=$c->editor->writes;$c->dispatch('saveevent');check($c->editor->writes===$before&&http_response_code()===403,'forged creation ticket rejected');
foreach(['calendar_csrf','calendar_stale','unexpected-private-detail'] as $error){$c->editor->error=$error;$c->params=['id'=>'qa-event','title'=>'Unsaved draft','details'=>'<p>Keep my text</p>','revision'=>'original','csrf_token'=>'expired'];$c->dispatch('updateevent');check($c->vars['draft']['title']==='Unsaved draft'&&$c->vars['draft']['details']==='<p>Keep my text</p>'&&$c->vars['draft']['revision']==='original','draft and revision retained');check($c->vars['calendarToken']==='fresh-token','renewed token');check($c->vars['calendarError']===($error==='unexpected-private-detail'?'calendar_uncertain':$error),'safe error');}
$a=new attachments();check($a->listFiles('../../etc')===[],'no path disclosure');foreach([['uploadFile',['../../etc']],['deleteFile',['../../etc','passwd']],['deleteAllFiles',['/']],['transfer',['../../etc','/tmp']]] as [$method,$args]){try{$a->$method(...$args);throw new LogicException('Unexpected filesystem access');}catch(RuntimeException $e){check($e->getMessage()==='calendar_attachments_retired','filesystem operation retired');}}
$c->objUser->logged=false;check($c->dispatch('add')===null&&http_response_code()===403,'anonymous boundary');
echo "PASS Calendar route methods, retired attachments, creation tickets, confirmation-only GET and safe draft recovery\n";
