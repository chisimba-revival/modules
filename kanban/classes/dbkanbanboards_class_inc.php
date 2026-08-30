<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbanboards extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_boards',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function inScope($type,$id,$archived=false){$rows=$this->getAll("WHERE scopetype=".$this->q($type)." AND scopeid=".$this->q($id).($archived?'':' AND isarchived=0').' ORDER BY isarchived,sortorder,title');return is_array($rows)?$rows:array();}
    public function ownedBy($userId,$archived=false){$rows=$this->getAll("WHERE ownerid=".$this->q($userId).($archived?'':' AND isarchived=0').' ORDER BY isarchived,sortorder,title');return is_array($rows)?$rows:array();}
    public function sharedWith($userId,$scopeType=null,$archived=false){$sql='SELECT b.* FROM tbl_kanban_boards b INNER JOIN tbl_kanban_access a ON a.boardid=b.id WHERE a.principaltype=\'user\' AND a.principalid='.$this->q($userId).($scopeType===null?'':' AND b.scopetype='.$this->q($scopeType)).($archived?'':' AND b.isarchived=0').' ORDER BY b.isarchived,b.sortorder,b.title';$rows=$this->getArray($sql);return is_array($rows)?$rows:array();}
    public function createBoard(array $data){$now=date('Y-m-d H:i:s');$data=array_merge($data,array('id'=>bin2hex(random_bytes(16)),'isarchived'=>0,'datecreated'=>$now,'datemodified'=>$now));return $this->insert($data);}
    public function saveBoard($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    public function removeBoard($id){return $this->delete('id',$id)!==false;}
    private function q($v){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$v):"'".str_replace("'","''",(string)$v)."'";}
}
?>
