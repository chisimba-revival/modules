<?php
/** Private, read-only attendee inspection using the same population as count pills. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarattendees extends ChisimbaObject
{
    public function listing($id, $page=1)
    {
        $record=$this->getObject('webinareditstore','webinar')->record($id);
        $policy=$this->getObject('webinareditpolicy','webinar');
        if(!$record||!$policy->canManage()||!$policy->canViewRegistrationCount($record,true))throw new DomainException('editor_forbidden');
        $store=$this->getObject('webinarregistrations','webinar');
        $total=$store->countsForManagement([$record])[$id]??0;
        $pages=max(1,(int)ceil($total/50));$page=max(1,min($pages,(int)$page));
        $rows=$store->selectPrepared("SELECT c.name,c.email,MAX(r.confirmed_at) AS confirmed_at FROM tbl_webinar_registrations r
            INNER JOIN tbl_audience_contacts c ON c.id=r.contact_id
            WHERE r.webinar_id=? AND r.state='confirmed' AND c.state='subscribed'
            AND r.contact_revision=c.revision GROUP BY c.id,c.name,c.email ORDER BY c.name,c.email,c.id LIMIT 50 OFFSET ".(($page-1)*50),[$id]);
        $this->loadClass('submissioncontentpolicy','abuseprotection');
        foreach($rows as &$row){
            $row['review']=!filter_var($row['email'],FILTER_VALIDATE_EMAIL)?'attendees_invalid_email':
                (SubmissionContentPolicy::suspiciousName($row['name'])?'attendees_generated_name':'');
        }
        unset($row);
        return ['record'=>$record,'rows'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages];
    }
}
