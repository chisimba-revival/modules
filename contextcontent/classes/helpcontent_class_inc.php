<?php
/** Native activity-report guidance. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) { return $topic === 'activityreports' && $this->getObject('user','security')->isLoggedIn(); }
    public function getTopic($topic) {
        if (!$this->mayViewTopic($topic)) return null;
        $t=fn($key)=>$this->getObject('language','language')->code2Txt('mod_contextcontent_report_'.$key,'contextcontent');
        return ['title'=>$t('activity'), 'summary'=>$t('help'), 'steps'=>[$t('helpdates'),$t('helppeople'),$t('helpcsv')], 'sections'=>[]];
    }
}
