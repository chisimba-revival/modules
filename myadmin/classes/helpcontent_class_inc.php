<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($id){return $id==='mail-report' && $this->getObject('user','security')->isAdmin();}
    public function getTopic($id){if(!$this->mayViewTopic($id))return null;$r=$this->getObject('mailreportview','myadmin');return ['title'=>$r->text('delivery_log'),'summary'=>$r->text('mail_report_help'),'steps'=>[$r->text('mail_log_steps'),$r->text('mail_privacy')]];}
}
