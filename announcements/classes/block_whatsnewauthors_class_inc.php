<?php
/** Instructor-facing sidebar summary of recent notices. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class block_whatsnewauthors extends ChisimbaObject
{
    public $title;
    public function init()
    {
        $this->language=$this->getObject('language','language');
        $this->items=$this->getObject('dbannouncements');
        $this->user=$this->getObject('user','security');
        $this->contexts=$this->getObject('usercontext','context');
        $this->dateTime=$this->getObject('timeanddateservice','timeanddate-service');
        $this->title=$this->language->code2Txt('mod_announcements_whatsnewauthors','announcements',NULL,"What’s new for [-authors-]");
    }
    public function show()
    {
        if(!$this->user->isAdmin()&&!count((array)$this->contexts->getContextWhereLecturer($this->user->userId()))) return '';
        $e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        $style='<link rel="stylesheet" href="'.$e($this->getResourceUri('announcements.css','announcements')).'?v=405">';
        $rows=$this->items->getLatestAuthorUpdates(4);
        if(!$rows) return $style.'<p>'.$e($this->language->languageText('mod_announcements_nonewupdates','announcements','No product updates have been published in the last seven days.')).'</p>';
        $out=$style.'<ul class="announcement-latest">';
        foreach($rows as $row){
            $url=$this->uri(array('action'=>'view','id'=>$row['id']),'announcements');
            $published=$row['publish_at']?:$row['createdon'];
            $excerpt=trim(preg_replace('/\s+/',' ',strip_tags((string)$row['message'])));
            if(mb_strlen($excerpt)>220) $excerpt=mb_substr($excerpt,0,217).'…';
            $out.='<li><p class="announcement-latest__heading"><time datetime="'.$e(substr((string)$published,0,10)).'">'.$e($this->dateTime->formatDate($published)).'.</time> <a href="'.$e($url).'">'.$e($row['title']).'</a></p>'.($excerpt!==''?'<p class="announcement-latest__excerpt">'.$e($excerpt).'</p>':'').(!empty($row['resource_url'])?'<p><a href="'.$e($row['resource_url']).'">'.$e($this->language->languageText('mod_announcements_userguide','announcements','Further information')).'</a></p>':'').'</li>';
        }
        return $out.'</ul><p><a href="'.$e($this->uri(NULL,'announcements')).'">'.$e($this->language->languageText('mod_announcements_viewall','announcements','View all updates')).'</a></p>';
    }
}
