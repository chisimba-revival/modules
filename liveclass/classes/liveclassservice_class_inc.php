<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class liveclassservice extends ChisimbaObject
{
    public function init(){ $this->provider=$this->getObject('bigbluebuttonprovider'); }
    public function available(){return $this->provider->isAvailable();}
    public function prepare(array $session,$logoutUrl){$session['logout_url']=$logoutUrl;return $this->provider->create($session,$this->password($session,'attendee'),$this->password($session,'moderator'));}
    public function joinUrl(array $session,$name,$userId,$moderator=false){return $this->provider->joinUrl($session,$name,$this->password($session,$moderator?'moderator':'attendee'),$userId);}
    public function end(array $session){return $this->provider->end($session,$this->password($session,'moderator'));}
    public function recordings(array $session){return $this->provider->recordings($session);}
    private function password(array $session,$role){$config=$this->getObject('dbsysconfig','sysconfig');$secret=(string)$config->getValue('LIVECLASS_BIGBLUEBUTTON_SECRET','liveclass');return substr(hash_hmac('sha256',$role.'|'.$session['provider_meeting_id'],$secret),0,24);}
}
?>
