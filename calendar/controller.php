<?php
/** Calendar routes use the shared identity/CSRF boundary and transactional editor. */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class calendar extends controller
{
    public $objUser,$objContext,$objLanguage,$objCalendarInterface,$contextCode,$contextTitle,$isInContext,$sideMenuBar;
    public function init(){
        $this->objUser=$this->getObject('user','security');$this->objContext=$this->getObject('dbcontext','context');
        $this->objLanguage=$this->getObject('language','language');$this->objCalendarInterface=$this->getObject('calendarinterface','calendar');
        $this->contextCode=$this->objContext->getContextCode()?:'root';$this->contextTitle=$this->objContext->getTitle();$this->isInContext=$this->contextCode!=='root';
        $this->setVar('fullname',$this->objUser->fullname());$this->setVar('contextCode',$this->contextCode);
    }
    public function requiresLogin($action){return true;}
    public function dispatch($action=null){
        $this->setVar('pageSuppressXML',true);
        $this->setLayoutTemplate($this->isInContext?'calendar_layout_tpl.php':'user_layout_tpl.php');
        if(!$this->objUser->isLoggedIn()){http_response_code(403);return null;}
        if(in_array($action,['tempframe','uploadattachment','downloadattachment','deleteattachment'],true)){
            http_response_code(410);$this->setVar('calendarError','calendar_attachments_retired');return 'security_notice_tpl.php';
        }
        if(in_array($action,['add','edit','saveevent','updateevent','delete'],true))return $this->editorPage($action);
        if($action==='icalexport'){$this->getObject('ical','calendar')->export();return null;}
        return $this->showEvents();
    }
    private function editorPage($action){
        $editor=$this->getObject('calendareditor','calendar');$method=strtoupper($_SERVER['REQUEST_METHOD']??'');
        $id=$this->getParam('id','');$id=is_string($id)?$id:'';$error='';
        $draft=['id'=>'','revision'=>'','creationid'=>'','contextcode'=>$this->contextCode,'date'=>date('Y-m-d'),'date2'=>date('Y-m-d'),'title'=>'','details'=>'','url'=>'','timefrom'=>'09:00','timeto'=>'10:00','eventfor'=>'0'];
        if(in_array($action,['edit','updateevent','delete'],true)){
            try{$draft=array_merge($draft,$editor->load($id));}
            catch(Throwable $e){http_response_code(403);$this->setVar('calendarError','calendar_denied');return 'security_notice_tpl.php';}
        }
        $keys=array_keys($draft);
        if(in_array($action,['saveevent','updateevent'],true) || ($action==='delete'&&$method==='POST')){
            if($method!=='POST'){http_response_code(405);$this->setVar('calendarError','calendar_method');return 'security_notice_tpl.php';}
            $input=[];
            foreach($keys as $key){
                $value=$this->getParam($key,($action==='delete'&&!in_array($key,['id','revision'],true))?$draft[$key]:'');
                $input[$key]=$value; // Preserve malformed values for strict service rejection.
                $draft[$key]=is_string($value)?$value:'';
            }
            $input['csrf_token']=$this->getParam('csrf_token','');
            try{
                $createId='';
                if($action==='saveevent'){
                    $issued=$this->getSession('calendarCreationIds',[]);
                    if(($issued[$draft['creationid']]??null)!==(string)$this->objUser->userId())throw new RuntimeException('calendar_denied');
                    $createId=$draft['creationid'];
                }
                $saved=$editor->mutate($action,$input,$method,$createId);
                if($action==='delete')return $this->nextAction(null,['message'=>'eventdeleted']);
                return $this->nextAction(null,['message'=>$action==='saveevent'?'eventadded':'eventupdated','month'=>(int)substr($draft['date'],5,2),'year'=>(int)substr($draft['date'],0,4)]);
            }catch(Throwable $e){
                $error=$e->getMessage();if(!in_array($error,['calendar_denied','calendar_method','calendar_csrf','calendar_invalid','calendar_stale'],true))$error='calendar_uncertain';
                http_response_code($error==='calendar_denied'||$error==='calendar_csrf'?403:422);
            }
        } elseif($action==='add'){
            $issued=$this->getSession('calendarCreationIds',[]);$issued=is_array($issued)?$issued:[];
            $draft['creationid']=bin2hex(random_bytes(16));$issued[$draft['creationid']]=(string)$this->objUser->userId();
            $this->setSession('calendarCreationIds',array_slice($issued,-30,null,true));
        }
        $this->setVar('draft',$draft);$this->setVar('calendarError',$error);$this->setVar('calendarToken',$editor->csrf()->issue('calendar_edit'));
        $this->setVar('deleteMode',$action==='delete');$this->setVar('calendarEditor',$editor);
        return 'addedit_event_tpl.php';
    }
    function showEvents() {
        $month = $this->getParam('month', date('m'));
        $year = $this->getParam('year', date('Y'));
        $groupid = $this->getParam("groupid");

        $this->setVarByRef('month', $month);
        $this->setVarByRef('year', $year);

        $this->objCalendarInterface->setupCalendar($month, $year);

        $eventsCalendar = $this->objCalendarInterface->getCalendar();

        $this->setVarByRef('userEvents', $this->objCalendarInterface->numUserEvents);
        $this->setVarByRef('contextEvents', $this->objCalendarInterface->numContextEvents);
        $this->setVarByRef('otherContextEvents', $this->objCalendarInterface->numOtherEvents);
        $this->setVarByRef('siteEvents', $this->objCalendarInterface->numSiteEvents);

        $this->setVarByRef('eventsCalendar', $eventsCalendar);
        $this->setVarByRef('calendarNavigation', $this->objCalendarInterface->getNav());
        $this->setVarByRef('eventsList', $this->objCalendarInterface->getEventsList());


        $this->setVarByRef("groupid", $groupid);

        return 'calendar_tpl.php';
    }

}
