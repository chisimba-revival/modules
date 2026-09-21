<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) { return $topic === 'protection' && $this->getObject('user','security')->isAdmin(); }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) { return null; }
        $language=$this->getObject('language','language');
        $t=fn($key)=>$language->languageText('mod_registration_service_help_'.$key,'registration-service');
        return array('title'=>$t('title'),'summary'=>$t('summary'),
            'steps'=>array($t('review'),$t('dismiss'),$t('expire'),$t('retention'),$t('settings'),$t('recovery')),'sections'=>array());
    }
}
