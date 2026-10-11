<?php
/** Registration persistence, independent of subscriber and site-account records. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarregistrations extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    {parent::init('tbl_webinar_registrations',$pearDb,$errorCallback);}
    public function one($id){return is_string($id)&&preg_match('/^[a-f0-9]{32}$/D',$id)?$this->getRow('id',$id):null;}
    public function token($token){return is_string($token)&&preg_match('/^[a-f0-9]{64}$/D',$token)?$this->getRow('confirm_hash',hash('sha256',$token)):null;}
    public function forContact($webinar,$contact)
    {if(!is_string($webinar)||!is_string($contact)||!preg_match('/^[a-f0-9]{32}$/D',$webinar)||!preg_match('/^[a-f0-9]{32}$/D',$contact))return null;return $this->getArray("SELECT * FROM tbl_webinar_registrations WHERE webinar_id='".$webinar."' AND contact_id='".$contact."' LIMIT 1")[0]??null;}
    /** Aggregate authorised current cards only; never load attendee identities for a badge. */
    public function countsForUpcoming(array $records)
    {
        return $this->countsForRecords($records,false);
    }
    /** Staff totals retain the same administrator/original-creator boundary. */
    public function countsForManagement(array $records)
    {
        return $this->countsForRecords($records,true);
    }
    private function countsForRecords(array $records,$management)
    {
        $this->loadClass('webinarschedule','webinar');
        $policy=$this->getObject('webinareditpolicy','webinar');$ids=[];
        foreach($records as $record){
            $id=$record['id']??null;
            if(is_string($id)&&preg_match('/^[a-f0-9]{32}$/D',$id)
                &&$policy->canViewRegistrationCount($record,$management)&&($management||webinarschedule::isCurrent($record)))$ids[$id]=0;
        }
        if(!$ids)return [];
        // Match alreadyRegistered(): confirmation, active subscription and current consent revision.
        $list=implode(',',array_fill(0,count($ids),'?'));
        $rows=$this->selectPrepared("SELECT r.webinar_id, COUNT(DISTINCT r.contact_id) AS total
            FROM tbl_webinar_registrations r INNER JOIN tbl_audience_contacts c ON c.id=r.contact_id
            WHERE r.webinar_id IN ($list) AND r.state='confirmed' AND c.state='subscribed'
            AND r.contact_revision=c.revision GROUP BY r.webinar_id",array_keys($ids));
        if(!is_array($rows))throw new RuntimeException('webinar_storage_failed');
        foreach($rows as $row)if(array_key_exists($row['webinar_id'],$ids))$ids[$row['webinar_id']]=(int)$row['total'];
        return $ids;
    }
    /** Lock the event before any contact read, so simultaneous bookings see fresh capacity.
     * Editors lock this same InnoDB row when changing the limit or closing registration.
     */
    public function lockWebinar($id)
    {
        return $this->selectPrepared('SELECT * FROM tbl_webinar_records WHERE id=? FOR UPDATE',[$id])[0]??null;
    }
    /** Pending email confirmations hold a place until their existing 24-hour expiry.
     * Exclude this contact when converting its reservation or retrying a booking.
     */
    public function occupied($webinar,$exceptContact,$now)
    {
        $rows=$this->selectPrepared("SELECT COUNT(DISTINCT r.contact_id) AS total
            FROM tbl_webinar_registrations r INNER JOIN tbl_audience_contacts c ON c.id=r.contact_id
            WHERE r.webinar_id=? AND r.contact_id<>? AND r.contact_revision=c.revision
            AND ((r.state='confirmed' AND c.state='subscribed') OR (r.state='pending' AND r.expires_at>?))",
            [$webinar,$exceptContact,$now]);
        return (int)$rows[0]['total'];
    }
    public function confirmed($webinar)
    {if(!preg_match('/^[a-f0-9]{32}$/D',$webinar))return [];return $this->getAll("WHERE webinar_id='$webinar' AND state='confirmed'")?:[];}
}
