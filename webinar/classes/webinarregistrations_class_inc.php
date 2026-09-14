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
    public function confirmed($webinar)
    {if(!preg_match('/^[a-f0-9]{32}$/D',$webinar))return [];return $this->getAll("WHERE webinar_id='$webinar' AND state='confirmed'")?:[];}
}
