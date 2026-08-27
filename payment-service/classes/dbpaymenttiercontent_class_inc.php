<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymenttiercontent extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_payment_service_tier_content',$pearDb,$errorCallback); }
    public function byTier($tier) { $row=$this->getRow('tier_code',$tier); return is_array($row)?$row:null; }
    public function saveTier($tier,array $fields) {
        $existing=$this->byTier($tier); $fields['updated_at']=date('Y-m-d H:i:s');
        if($existing) return $this->update('tier_code',$tier,$fields)!==false;
        $fields['id']=bin2hex(random_bytes(16));$fields['tier_code']=$tier;$fields['created_at']=$fields['updated_at'];
        return $this->insert($fields)!==false;
    }
}
?>
