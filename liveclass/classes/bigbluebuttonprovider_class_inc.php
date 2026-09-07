<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class bigbluebuttonprovider extends ChisimbaObject
{
    public function init(){ $this->config=$this->getObject('dbsysconfig','sysconfig'); }
    public function isAvailable(){return $this->endpoint()!==''&&$this->secret()!==''&&function_exists('curl_init');}
    public function signedUrl($call,array $parameters){ksort($parameters);$query=http_build_query($parameters,'','&',PHP_QUERY_RFC3986);return $this->endpoint().'/api/'.$call.'?'.$query.'&checksum='.sha1($call.$query.$this->secret());}
    public function create(array $session,$attendeePassword,$moderatorPassword){return $this->request('create',array('meetingID'=>$session['provider_meeting_id'],'name'=>$session['name'],'attendeePW'=>$attendeePassword,'moderatorPW'=>$moderatorPassword,'record'=>!empty($session['record_session'])?'true':'false','duration'=>(int)$session['duration_minutes'],'logoutURL'=>$session['logout_url']??''));}
    public function joinUrl(array $session,$fullName,$password,$userId){return $this->signedUrl('join',array('meetingID'=>$session['provider_meeting_id'],'fullName'=>$fullName,'password'=>$password,'userID'=>$userId,'redirect'=>'true'));}
    public function end(array $session,$moderatorPassword){return $this->request('end',array('meetingID'=>$session['provider_meeting_id'],'password'=>$moderatorPassword));}
    public function recordings(array $session){$result=$this->request('getRecordings',array('meetingID'=>$session['provider_meeting_id']));return !empty($result['ok'])?$result['recordings']:array();}
    private function request($call,array $parameters){if(!$this->isAvailable())return array('ok'=>false,'code'=>'provider_unavailable');$handle=curl_init($this->signedUrl($call,$parameters));curl_setopt_array($handle,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false));$body=curl_exec($handle);$status=(int)curl_getinfo($handle,CURLINFO_HTTP_CODE);curl_close($handle);if($body===false||$status<200||$status>=300)return array('ok'=>false,'code'=>'provider_request_failed');$xml=@simplexml_load_string($body);if(!$xml||strtoupper((string)$xml->returncode)!=='SUCCESS')return array('ok'=>false,'code'=>(string)($xml->messageKey??'provider_response_failed'));$recordings=array();foreach(($xml->recordings->recording??array()) as $recording){$formats=array();foreach(($recording->playback->format??array()) as $format)$formats[]=array('type'=>(string)$format->type,'url'=>(string)$format->url);$recordings[]=array('id'=>(string)$recording->recordID,'name'=>(string)$recording->name,'published'=>(string)$recording->published==='true','formats'=>$formats); }return array('ok'=>true,'recordings'=>$recordings);}
    private function endpoint(){return rtrim(trim((string)$this->config->getValue('LIVECLASS_BIGBLUEBUTTON_ENDPOINT','liveclass')),'/');}
    private function secret(){return trim((string)$this->config->getValue('LIVECLASS_BIGBLUEBUTTON_SECRET','liveclass'));}
}
?>
