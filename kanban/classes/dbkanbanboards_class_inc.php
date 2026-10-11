<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbanboards extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_boards',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function inScope($type,$id,$archived=false) { return $this->selectPrepared('SELECT * FROM tbl_kanban_boards WHERE scopetype=? AND scopeid=?'.($archived?'':' AND isarchived=0').' ORDER BY isarchived,sortorder,title', [$type,$id]); }
    public function ownedBy($userId,$archived=false) { return $this->selectPrepared('SELECT * FROM tbl_kanban_boards WHERE ownerid=?'.($archived?'':' AND isarchived=0').' ORDER BY isarchived,sortorder,title', [$userId]); }
    public function sharedWith($userId,$scopeType=null,$archived=false) {
        $params=[$userId]; if ($scopeType!==null) $params[]=$scopeType;
        return $this->selectPrepared("SELECT b.* FROM tbl_kanban_boards b INNER JOIN tbl_kanban_access a ON a.boardid=b.id WHERE a.principaltype='user' AND a.principalid=?".($scopeType===null?'':' AND b.scopetype=?').($archived?'':' AND b.isarchived=0').' ORDER BY b.isarchived,b.sortorder,b.title', $params);
    }
    public function createBoard(array $data){$now=date('Y-m-d H:i:s');$data=array_merge($data,array('id'=>bin2hex(random_bytes(16)),'isarchived'=>0,'datecreated'=>$now,'datemodified'=>$now));return $this->insert($data);}
    public function saveBoard($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    public function setSortOrder($id,$sortOrder){return $this->update('id',$id,array('sortorder'=>(int)$sortOrder))!==false;}
    public function removeBoard($id){return $this->delete('id',$id)!==false;}

}
?>
