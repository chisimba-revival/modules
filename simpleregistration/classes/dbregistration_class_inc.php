<?php
/* 
 * Responsibl for insterting, updating and deleting schedules table
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']){
    die('You cannot view this page directly');
}
class dbregistration extends dbTable{

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_simpleregistrationmembers');  //super
        $this->table = 'tbl_simpleregistrationmembers';
        $this->objUser = $this->getObject ( 'user', 'security' );

    }

 
   
    public function addRegistration(

        $firstname,
        $lastname,
        $company,
        $email,
        $eventid){

        $data = array(
            'first_name' => $firstname,
            'last_name' => $lastname,
            'event_id' => $eventid,
            'registration_date' => strftime('%Y-%m-%d %H:%M:%S', time()),
            'email'=>$email,
            'company'=>$company,
        );

        if($this->emailExists($email,$eventid)){
            return FALSE;
        }else{
            $regId = $this->insert($data);
            return $regId;
        }
    }

    public function getRegistrations($eventid)
    {
        $sql="select * from ".$this->table." where event_id ='".$eventid."'";
        $rows=$this->getArray($sql);
        return $rows;
    }

    public function getUserDetails($username)
    {
        $sql="select * from  tbl_users where username= '".$username."'";
        $rows=$this->getArray($sql);

        return $rows;
    }
    public function emailExists($email,$eventid)
    {
        $sql="select * from " .$this->table." where email = '".$email."' and event_id ='".$eventid."'";
        $rows=$this->getArray($sql);
        return (is_countable($rows) ? count($rows) : 0) > 0 ? TRUE:FALSE;
    }
    public function deletemember($id)
    {
        $sql="delete from " .$this->table." where id = '".$id."'";
        $rows=$this->getArray($sql);
        return $rows;
    }

      public function deleteEventMembers($id)
    {
        if ($id != '') {
            return $this->delete('event_id', $id);
        }
    }


    public  function getRegistrationCount($eventid){
            $sql="select count(id) as totalregistrations from ".$this->table." where event_id ='".$eventid."'";
            $rows=$this->getArray($sql);
            if((is_countable($rows) ? count($rows) : 0) > 0){
                $row=$rows[0];
                return $row['totalregistrations'];
            }
            return 0;
    }
}
?>
