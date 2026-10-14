<?php
/** Triple Store administration guidance. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic)
    {
        $user = $this->getObject('user','security');
        return $topic === 'manage' && $user->isLoggedIn() && $user->isAdmin();
    }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) return null;
        $t = fn($key) => $this->getObject('language','language')->languageText('mod_triplestore_'.$key,'triplestore');
        return ['title'=>$t('title'),'summary'=>$t('intro'), 'steps'=>[$t('helpfilter'),$t('helpedit'),$t('helpdelete'),$t('helprecovery')], 'sections'=>[]];
    }
}
