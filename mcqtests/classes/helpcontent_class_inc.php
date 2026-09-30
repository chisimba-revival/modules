<?php
/** Question-bank guidance through the shared Help drawer. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) { return $topic==='banks' && $this->getObject('user','security')->isLoggedIn(); }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) return null;
        $t=fn($key)=>ucfirst($this->getObject('language','language')->code2Txt('mod_mcqtests_bank_'.$key,'mcqtests'));
        return ['title'=>$t('title'),'summary'=>$t('intro'),'steps'=>[$t('help_create'),$t('help_add'),$t('help_pull')],
            'sections'=>[['heading'=>$t('sharing'),'body'=>$t('sharing_help')],['heading'=>$t('duplicates'),'body'=>$t('conflict_help')],['heading'=>$t('recovery'),'body'=>$t('help_recovery')]]];
    }
}
