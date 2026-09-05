<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class db_contextcontent_section_acknowledgements extends dbtable {
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') { parent::init('tbl_contextcontent_section_acknowledgements'); }
    public function has($contextCode,$sectionId,$userId) {
        return $this->getRecordCount("WHERE contextcode='".addslashes((string)$contextCode)."' AND sectionid='".addslashes((string)$sectionId)."' AND userid='".addslashes((string)$userId)."'")>0;
    }
    public function acknowledge($contextCode,$sectionId,$userId) {
        if ($this->has($contextCode,$sectionId,$userId)) { return TRUE; }
        return $this->insert(array('contextcode'=>$contextCode,'sectionid'=>$sectionId,'userid'=>$userId,'acknowledgedat'=>date('Y-m-d H:i:s')));
    }
}
?>
