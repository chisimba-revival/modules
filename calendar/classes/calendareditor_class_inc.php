<?php
/** Authorisation and validation for the maintained Calendar editor. */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class calendareditor extends ChisimbaObject
{
    public function init(){}
    public function csrf(){return $this->getObject('nativeauthwebcomposition','security')->build()['csrf'];}
    public function mayManage($row): bool {
        $user=$this->getObject('user','security');
        if(!$user->isLoggedIn() || !is_array($row) || !empty($row['moduleevent_table']) || !empty($row['moduleevent_id']))return false;
        if((string)$row['userorcontext']==='0')return (string)$row['userfirstentry']===(string)$user->userId();
        if((string)$row['userorcontext']==='3')return $user->isAdmin();
        if((string)$row['userorcontext']!=='1')return false;
        $code=(string)$row['context'];
        if($code==='root')return $user->isAdmin();
        return $code!=='' && (bool)$this->getObject('dbcontext','context')->getContextDetails($code)
            && ($user->isAdmin() || $user->isContextLecturer($user->userId(),$code));
    }
    public function scope(string $type): array {
        $user=$this->getObject('user','security');$context=$this->getObject('dbcontext','context');
        $row=['userorcontext'=>$type==='3'?'1':$type,'context'=>$type==='1'?(string)$context->getContextCode():'root','userfirstentry'=>$user->userId()];
        if(!in_array($type,['0','1','3'],true)||($type==='1'&&in_array($row['context'],['','root'],true))||!$this->mayManage($row))throw new RuntimeException('calendar_denied');
        return $row;
    }
    public function load(string $id): array {
        $repo=$this->getObject('dbcalendarentries','calendar');$rows=$repo->batch($id);
        foreach($rows as $row)if(!$this->mayManage($row))throw new RuntimeException('calendar_denied');
        $first=$rows[0];$last=$rows[count($rows)-1];
        return ['id'=>$first['id'],'revision'=>$repo->revision($rows),'date'=>$first['eventdate'],'date2'=>$last['eventdate'],'title'=>$first['eventtitle'],'details'=>$first['eventdetails'],'url'=>$first['eventurl']??'','timefrom'=>substr((string)$first['timefrom'],0,5),'timeto'=>substr((string)$first['timeto'],0,5),'eventfor'=>(string)$first['userorcontext']==='0'?'0':($first['context']==='root'?'3':'1')];
    }
    public function mutate(string $action,array $input,string $method,string $createId=''): string {
        if(!$this->getObject('user','security')->isLoggedIn())throw new RuntimeException('calendar_denied');
        if($method!=='POST')throw new RuntimeException('calendar_method');
        if(!is_string($input['csrf_token']??null)||!$this->csrf()->consume('calendar_edit',$input['csrf_token']))throw new RuntimeException('calendar_csrf');
        foreach($input as $value)if(!is_string($value))throw new RuntimeException('calendar_invalid');
        $repo=$this->getObject('dbcalendarentries','calendar');$id=$input['id']??'';$revision=$input['revision']??'';
        $allowed=fn($row)=>$this->mayManage($row);
        if($action==='delete'){$repo->deleteSeries($id,$revision,$allowed);return $id;}
        if(!in_array($action,['saveevent','updateevent'],true)||($action==='updateevent'&&$id===''))throw new RuntimeException('calendar_denied');
        if($action==='saveevent'&&($id!==''||!preg_match('/^[a-f0-9]{32}$/D',$createId)))throw new RuntimeException('calendar_denied');
        if($id==='' && ($input['contextcode']??'')!==(string)($this->getObject('dbcontext','context')->getContextCode()?:'root'))throw new RuntimeException('calendar_stale');
        $original=$id!==''?$repo->batch($id)[0]:$this->scope($input['eventfor']??'0');
        if(!$this->mayManage($original))throw new RuntimeException('calendar_denied');
        $dates=[];$start=$this->date($input['date']??'');$end=$this->date($input['date2']??'');
        if($end<$start || $start->diff($end)->days>365)throw new RuntimeException('calendar_invalid');
        for($day=$start;$day<=$end;$day=$day->modify('+1 day'))$dates[]=$day->format('Y-m-d');
        $title=trim($input['title']??'');$details=$input['details']??'';$url=trim($input['url']??'');
        $from=$input['timefrom']??'';$to=$input['timeto']??'';
        if($title===''||mb_strlen($title)>100||strlen($details)>1000000||strlen($url)>100||($url!==''&&(!filter_var($url,FILTER_VALIDATE_URL)||!preg_match('~^https?://~i',$url)))||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$from)||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$to)||$to<$from)throw new RuntimeException('calendar_invalid');
        $uid=$this->getObject('user','security')->userId();$today=date('Y-m-d');
        $values=['eventtitle'=>$title,'eventdetails'=>$this->getObject('htmlcleaner','utilities')->cleanHtml($details),'eventurl'=>$url,'timefrom'=>$from.':00','timeto'=>$to.':00','userorcontext'=>$original['userorcontext'],'context'=>$original['context'],'userFirstEntry'=>$original['userfirstentry'],'dateFirstEntry'=>$original['datefirstentry']??$today,'userLastModified'=>$uid,'dateLastModified'=>$today];
        return $repo->saveSeries($values,$dates,$id,$revision,$createId,$allowed);
    }
    private function date(string $value): DateTimeImmutable {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        if(!$date||$date->format('Y-m-d')!==$value||$date->format('Y')<'1900'||$date->format('Y')>'2100')throw new RuntimeException('calendar_invalid');
        return $date;
    }
}
