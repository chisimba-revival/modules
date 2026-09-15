<?php
/** Storage for guest orders; payment state remains owned by paymentservice. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class dbcontributions extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_payment_service_contributions',$pearDb,$errorCallback); }
    public function byId($id) { if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))return null;$row=$this->getRow('id',$id);return is_array($row)?$row:null; }
    public function byToken($token) { if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))return null;$row=$this->getRow('access_hash',hash('sha256',$token));return is_array($row)?$row:null; }
    public function create(array $row) { return $this->insert($row)!==false; }
}
