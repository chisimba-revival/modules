<?php
/**
 * Persistence gateway for knowledge-map nodes.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Stores graph nodes independently from relationships and view state. */
class dbknowledgemapnodes extends dbTable
{
    /** Initialise the node table gateway. */
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_nodes',$pearDb,$errorCallback);$this->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');}

    /** Return all nodes for a map. */
    public function forMap($mapId){$rows=$this->getAll('WHERE mapid='.$this->q($mapId).' ORDER BY sortorder,id');return is_array($rows)?$rows:array();}

    /** Insert a normalised graph node. */
    public function addNode($mapId,array $node){$now=date('Y-m-d H:i:s');return $this->insert(array('id'=>$node['id'],'mapid'=>$mapId,'nodetype'=>$node['type'],'title'=>$node['title'],'description'=>$node['description'],'presentation'=>json_encode($node['presentation'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'sortorder'=>$node['order'],'datecreated'=>$now,'datemodified'=>$now));}

    /** Remove every node belonging to a map. */
    public function removeForMap($mapId){return $this->query('DELETE FROM tbl_knowledgemap_nodes WHERE mapid='.$this->q($mapId))!==false;}

    /** Quote one scalar for the active database driver. */
    private function q($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
