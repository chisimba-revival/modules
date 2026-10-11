<?php
/**
 * Persistence gateway for typed knowledge-map relationships.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Stores graph edges without rebuilding them from a parent cache. */
class dbknowledgemaprelationships extends dbTable
{
    /** Initialise the relationship table gateway. */
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_relationships',$pearDb,$errorCallback);$this->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');}

    /** Return all relationships for a map. */
    public function forMap($id) { return $this->selectPrepared('SELECT * FROM tbl_knowledgemap_relationships WHERE mapid=? ORDER BY sortorder,id', [$id]); }

    /** Insert one normalised graph relationship. */
    public function addRelationship($mapId,array $relationship){$result=$this->insert(array('id'=>$relationship['id'],'mapid'=>$mapId,'relationshiptype'=>$relationship['type'],'fromnodeid'=>$relationship['from'],'tonodeid'=>$relationship['to'],'externaltarget'=>$relationship['externalTarget'],'properties'=>json_encode($relationship['properties'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'sortorder'=>$relationship['order'],'datecreated'=>date('Y-m-d H:i:s')));if($result===false)throw new RuntimeException('Graph insertion failed');return $result;}

    /** Remove every relationship belonging to a map. */
    public function removeForMap($id) { $this->executePrepared('DELETE FROM tbl_knowledgemap_relationships WHERE mapid=?', [$id]); return true; }

}
?>
