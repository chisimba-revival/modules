<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class db_contextcontent_sections extends dbtable {
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') { parent::init('tbl_contextcontent_sections'); $this->objUser=$this->getObject('user','security'); }
    public function forContext($contextCode, $includeHidden=FALSE) {
        $where="WHERE contextcode='".addslashes((string)$contextCode)."'";
        if (!$includeHidden) { $where.=" AND visibility='Y'"; }
        return $this->getAll($where.' ORDER BY sectionorder, datecreated');
    }
    public function inContext($id,$contextCode) {
        $rows=$this->getAll("WHERE id='".addslashes((string)$id)."' AND contextcode='".addslashes((string)$contextCode)."' LIMIT 1");
        return empty($rows)?FALSE:$rows[0];
    }
    public function saveSection($contextCode,$id,$title,$introduction,$visibility) {
        $now=date('Y-m-d H:i:s'); $user=$this->objUser->userId();
        if ($id!=='') {
            if ($this->inContext($id,$contextCode)===FALSE) { return FALSE; }
            return $this->update('id',$id,array('title'=>$title,'introduction'=>$introduction,'visibility'=>$visibility,'modifierid'=>$user,'datemodified'=>$now));
        }
        $all=$this->forContext($contextCode,TRUE); $order=empty($all)?1:((int)$all[count($all)-1]['sectionorder']+1);
        return $this->insert(array('contextcode'=>$contextCode,'title'=>$title,'introduction'=>$introduction,'sectionorder'=>$order,'visibility'=>$visibility,'creatorid'=>$user,'datecreated'=>$now));
    }
    public function removeFromContext($id,$contextCode) {
        if ($this->inContext($id,$contextCode)===FALSE) { return FALSE; }
        return $this->delete('id',$id);
    }
    public function move($id,$contextCode,$direction) {
        $current=$this->inContext($id,$contextCode);
        if ($current===FALSE || !in_array($direction,array('up','down'),TRUE)) { return FALSE; }
        $operator=$direction==='up'?'<':'>';
        $order=$direction==='up'?'DESC':'ASC';
        $rows=$this->getAll("WHERE contextcode='".addslashes((string)$contextCode)."' AND sectionorder ".$operator.' '.(int)$current['sectionorder'].' ORDER BY sectionorder '.$order.' LIMIT 1');
        if (empty($rows)) { return FALSE; }
        $other=$rows[0];
        $this->update('id',$current['id'],array('sectionorder'=>$other['sectionorder']));
        return $this->update('id',$other['id'],array('sectionorder'=>$current['sectionorder']));
    }
}
?>
