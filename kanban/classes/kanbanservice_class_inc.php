<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class kanbanservice extends object
{
    public function init(){ $this->boards=$this->getObject('dbkanbanboards');$this->tasks=$this->getObject('dbkanbantasks');$this->subtasks=$this->getObject('dbkanbansubtasks');$this->access=$this->getObject('dbkanbanaccess');$this->auth=$this->getObject('kanbanauthorizationservice'); }
    public function board($id,$permission='view'){$board=$this->boards->one($id);return $board&&$this->auth->allows($board,$permission)?$board:false;}
    public function hydrate(array $boards){foreach($boards as &$board){$board['tasks']=$this->tasks->forBoard($board['id']);foreach($board['tasks'] as &$task)$task['subtasks']=$this->subtasks->forTask($task['id']);$board['permission']=$this->auth->allows($board,'manage')?'manage':($this->auth->allows($board,'edit')?'edit':'view');}return $boards;}
}
?>
