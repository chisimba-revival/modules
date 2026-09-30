<?php
/** Verify additive bank schema and enforce identity on repeatable upgrades. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class mcqtests_installscripts extends ChisimbaObject
{
    public function postinstall($version=null)
    {
        $admin=$this->getObject('modulesadmin','modulecatalogue');$db=$this->objEngine->getDbObj();
        $tables=$admin->listDbTables();
        foreach (['tbl_mcq_banks','tbl_mcq_bank_items','tbl_mcq_question_meta'] as $table) {
            if (!is_array($tables)||!in_array($table,$tables,true)) throw new RuntimeException('Bank schema installation failed');
            $constraints=$db->mgListTableConstraints($table);
            if (!is_array($constraints)) throw new RuntimeException('Bank constraint inspection failed');
            $definitions=['mcqbank_id'=>['id'=>[]]];
            if($table==='tbl_mcq_bank_items')$definitions['mcqbank_identity']=['bankid'=>[],'fingerprint'=>[]];
            foreach ($definitions as $name=>$fields) if (!in_array($name,$constraints,true)) {
                $result=$admin->createConstraint($table,$name,['unique'=>true,'fields'=>$fields]);
                if ($result===false||PEAR::isError($result)) throw new RuntimeException('Bank constraint installation failed');
            }
        }
    }
}
