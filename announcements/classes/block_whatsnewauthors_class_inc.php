<?php
/** Instructor-facing sidebar summary of approved product updates. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class block_whatsnewauthors extends ChisimbaObject
{
    public $title;
    public function init(){$this->language=$this->getObject('language','language');$this->items=$this->getObject('dbannouncements');$this->user=$this->getObject('user','security');$this->contexts=$this->getObject('usercontext','context');$this->title=$this->language->code2Txt('mod_announcements_whatsnewauthors','announcements',NULL,"What’s new for [-authors-]");}
    public function show(){if(!$this->user->isAdmin()&&!count((array)$this->contexts->getContextWhereLecturer($this->user->userId())))return '';$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$rows=$this->items->getLatestAuthorUpdates(3);if(!$rows)return '<p>'.$e($this->language->languageText('mod_announcements_nonewupdates','announcements','No product updates have been published yet.')).'</p>';$out='<ol class="announcement-latest">';foreach($rows as $row){$url=$this->uri(array('action'=>'view','id'=>$row['id']),'announcements');$summary=trim((string)$row['summary']);$out.='<li><time datetime="'.$e(substr((string)($row['publish_at']?:$row['createdon']),0,10)).'">'.$e(date('j M Y',strtotime($row['publish_at']?:$row['createdon']))).'</time><a href="'.$e($url).'">'.$e($row['title']).'</a>'.($summary!==''?'<p>'.$e($summary).'</p>':'').'</li>';}$out.='</ol><p><a href="'.$e($this->uri(NULL,'announcements')).'">'.$e($this->language->languageText('mod_announcements_viewall','announcements','View all updates')).'</a></p>';return $out;}
}
