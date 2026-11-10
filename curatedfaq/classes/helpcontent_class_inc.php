<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function init(){}
    public function mayViewTopic($id){return $id==='reading'||($id==='editing'&&$this->getObject('faqservice','curatedfaq')->canManage());}
    public function getTopic($id)
    {
        if(!$this->mayViewTopic($id))return null;
        $t=fn($key)=>$this->getObject('language','language')->code2Txt('mod_curatedfaq_'.$key,'curatedfaq');
        return ['title'=>$t('help_'.$id),'summary'=>$t('help_'.$id.'_summary'),'steps'=>[$t('help_'.$id.'_steps')],
            'sections'=>$id==='editing'?[['heading'=>$t('restore'),'body'=>$t('help_recovery')]]:[]];
    }
}
