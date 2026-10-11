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
    public function forMap($id) { return $this->selectPrepared('SELECT * FROM tbl_knowledgemap_nodes WHERE mapid=? ORDER BY sortorder,id', [$id]); }

    /** Insert a normalised graph node. */
    public function addNode($mapId,array $node){$now=date('Y-m-d H:i:s');$result=$this->insert(array('id'=>$node['id'],'mapid'=>$mapId,'nodetype'=>$node['type'],'title'=>$node['title'],'description'=>$node['description'],'presentation'=>json_encode($node['presentation'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'sortorder'=>$node['order'],'datecreated'=>$now,'datemodified'=>$now));if($result===false)throw new RuntimeException('Graph insertion failed');return $result;}

    /** Remove every node belonging to a map. */
    public function removeForMap($id) { $this->executePrepared('DELETE FROM tbl_knowledgemap_nodes WHERE mapid=?', [$id]); return true; }

}
?>
