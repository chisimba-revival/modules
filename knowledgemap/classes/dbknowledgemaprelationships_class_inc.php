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
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_relationships',$pearDb,$errorCallback);}

    /** Return all relationships for a map. */
    public function forMap($mapId){$rows=$this->getAll('WHERE mapid='.$this->q($mapId).' ORDER BY sortorder,id');return is_array($rows)?$rows:array();}

    /** Insert one normalised graph relationship. */
    public function addRelationship($mapId,array $relationship){return $this->insert(array('id'=>$relationship['id'],'mapid'=>$mapId,'relationshiptype'=>$relationship['type'],'fromnodeid'=>$relationship['from'],'tonodeid'=>$relationship['to'],'externaltarget'=>$relationship['externalTarget'],'properties'=>json_encode($relationship['properties'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'sortorder'=>$relationship['order'],'datecreated'=>date('Y-m-d H:i:s')));}

    /** Remove every relationship belonging to a map. */
    public function removeForMap($mapId){return $this->query('DELETE FROM tbl_knowledgemap_relationships WHERE mapid='.$this->q($mapId))!==false;}

    /** Quote one scalar for the active database driver. */
    private function q($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
