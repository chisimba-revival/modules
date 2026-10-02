<?php
/**
 * Kanban integration with canonical Chisimba Notes.
 *
 * @author Derek Keats
 * @package kanban
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Resolves visible notes and creates durable board and task connections. */
class kanbannoteservice extends controller
{
    private $user;
    private $notes;
    private $links;
    private $authorization;

    /** Initialise Notes integration services. */
    public function init()
    {
        $this->user=$this->getObject('user','security');
        $this->notes=$this->getObject('dbnotes','pagenotes');
        $this->links=$this->getObject('dbnotelinks','pagenotes');
        $this->authorization=$this->getObject('noteauthorizationservice','pagenotes');
    }

    /** Add authorized note summaries and connection choices to hydrated boards. */
    public function enrich(array $boards)
    {
        foreach($boards as &$board){
            $board['linkednotes']=$this->linked('kanban_board',$board['id']);
            $board['availablenotes']=$this->available($board);
            foreach($board['tasks'] as &$task)$task['linkednotes']=$this->linked('kanban_task',$task['id']);
            unset($task);
        }
        unset($board);
        return $boards;
    }

    /** Return notes the current user may attach within a board's working scope. */
    public function available(array $board)
    {
        $rows=array_merge($this->notes->ownedBy($this->user->userId()),$this->notes->sharedWith($this->user->userId()));
        if($board['scopetype']!=='personal')$rows=array_merge($rows,$this->notes->inScope($board['scopetype'],$board['scopeid']));
        $result=array();
        foreach($rows as $note)if(empty($note['isarchived'])&&$this->authorization->allows($note,'edit'))$result[$note['id']]=$note;
        uasort($result,fn($a,$b)=>strcasecmp($a['title'],$b['title']));
        return array_values($result);
    }

    /** Create or select a note, then attach it to a board or task. */
    public function connect(array $board,$type,$targetId,$targetLabel,$noteId,$newTitle,$targetUrl)
    {
        $note=false;
        if($noteId!==''){
            $note=$this->notes->one($noteId);
            if(!$note||!empty($note['isarchived'])||!$this->authorization->allows($note,'edit'))return false;
        }else{
            if($newTitle===''||!$this->authorization->canCreate($board['scopetype'],$board['scopeid']))return false;
            $id=$this->notes->createNote(array('scopetype'=>$board['scopetype'],'scopeid'=>$board['scopeid'],'ownerid'=>$this->user->userId(),'title'=>$newTitle,'body'=>''));
            $note=$id?$this->notes->one($id):false;
            if(!$note)return false;
        }
        if(!$this->links->addLink($note['id'],$type,$targetId,$targetLabel,$targetUrl,$this->user->userId()))return false;
        return $note;
    }

    /** Remove backlinks to a Kanban resource that is being permanently deleted. */
    public function removeTarget($type,$id){return $this->links->removeTarget($type,$id);}

    /** Return authorized active notes attached to one typed Kanban target. */
    private function linked($type,$id)
    {
        $result=array();
        foreach($this->links->noteIdsForTarget($type,$id) as $noteId){$note=$this->notes->one($noteId);if($note&&empty($note['isarchived'])&&$this->authorization->allows($note,'view'))$result[$note['id']]=$note;}
        return array_values($result);
    }
}
?>
