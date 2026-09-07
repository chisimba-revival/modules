<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class liveclasslifecycle extends ChisimbaObject
{
    public function init(){ $this->config=$this->getObject('dbsysconfig','sysconfig'); }
    public function joinWindowMinutes(){ $value=(int)$this->config->getValue('LIVECLASS_JOIN_WINDOW_MINUTES','liveclass');return max(0,min(180,$value?:15)); }
    public function state(array $session,$now=null){
        $now=$now===null?time():(int)$now;$start=strtotime($session['starts_at']);$end=$start+max(5,(int)$session['duration_minutes'])*60;$opens=$start-$this->joinWindowMinutes()*60;
        if(($session['status']??'')==='cancelled')return array('phase'=>'cancelled','can_join'=>false,'opens_at'=>$opens,'starts_at'=>$start,'ends_at'=>$end);
        if(($session['status']??'')==='ended'||$now>$end)return array('phase'=>'finished','can_join'=>false,'opens_at'=>$opens,'starts_at'=>$start,'ends_at'=>$end);
        if($now<$opens)return array('phase'=>'upcoming','can_join'=>false,'opens_at'=>$opens,'starts_at'=>$start,'ends_at'=>$end);
        return array('phase'=>'live','can_join'=>true,'opens_at'=>$opens,'starts_at'=>$start,'ends_at'=>$end);
    }
}
?>
