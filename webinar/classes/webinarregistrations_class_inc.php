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
        $this->loadClass('webinarschedule','webinar');
        $policy=$this->getObject('webinareditpolicy','webinar');$ids=[];
        foreach($records as $record){
            $id=$record['id']??null;
            if(is_string($id)&&preg_match('/^[a-f0-9]{32}$/D',$id)
                &&$policy->canViewRegistrationCount($record)&&webinarschedule::isCurrent($record))$ids[$id]=0;
        }
        if(!$ids)return [];
        // Match alreadyRegistered(): confirmation, active subscription and current consent revision.
        $list="'".implode("','",array_keys($ids))."'";
        $rows=$this->getArray("SELECT r.webinar_id, COUNT(DISTINCT r.contact_id) AS total
            FROM tbl_webinar_registrations r INNER JOIN tbl_audience_contacts c ON c.id=r.contact_id
            WHERE r.webinar_id IN ($list) AND r.state='confirmed' AND c.state='subscribed'
            AND r.contact_revision=c.revision GROUP BY r.webinar_id");
        if(!is_array($rows))throw new RuntimeException('webinar_storage_failed');
        foreach($rows as $row)if(array_key_exists($row['webinar_id'],$ids))$ids[$row['webinar_id']]=(int)$row['total'];
        return $ids;
    }
    public function confirmed($webinar)
    {if(!preg_match('/^[a-f0-9]{32}$/D',$webinar))return [];return $this->getAll("WHERE webinar_id='$webinar' AND state='confirmed'")?:[];}
}
