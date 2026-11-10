<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function init(){}
    public function mayViewTopic($id){return $id==='viewing';}
    public function getTopic($id){if(!$this->mayViewTopic($id))return null;$l=$this->getObject('language','language');$t=fn($k)=>$l->code2Txt('mod_photogallery_'.$k,'photogallery');return ['title'=>$t('help_title'),'summary'=>$t('help_summary'),'steps'=>[$t('help_steps')],'sections'=>[]];}
}
