<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function init(){}
    public function mayViewTopic($id){return $id==='sending'||($id==='managing'&&$this->getObject('contactservice','contactform')->canManage());}
    public function getTopic($id){if(!$this->mayViewTopic($id))return null;$t=fn($key)=>$this->getObject('language','language')->code2Txt('mod_contactform_'.$key,'contactform');return ['title'=>$t('help_'.$id),'summary'=>$t('help_'.$id.'_summary'),'steps'=>[$t('help_'.$id.'_steps')]];}
}
