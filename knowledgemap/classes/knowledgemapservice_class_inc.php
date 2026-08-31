<?php
/**
 * Application service for loading Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Provides authorized maps and serialisable graph documents. */
class knowledgemapservice extends controller
{
    private $maps;
    private $nodes;
    private $relationships;
    private $authorization;
    private $graph;

    /** Initialise map application dependencies. */
    public function init(){$this->maps=$this->getObject('dbknowledgemaps');$this->nodes=$this->getObject('dbknowledgemapnodes');$this->relationships=$this->getObject('dbknowledgemaprelationships');$this->authorization=$this->getObject('knowledgemapauthorizationservice');$this->graph=$this->getObject('knowledgemapgraphservice');}

    /** Return one map when the current user has the requested permission. */
    public function map($id,$permission='view'){$map=$this->maps->one($id);return $map&&$this->authorization->allows($map,$permission)?$map:false;}

    /** Hydrate one authorized map into the canonical graph document envelope. */
    public function document($id,$permission='view'){$map=$this->map($id,$permission);if(!$map)return false;$nodes=array_map(function($row){$presentation=json_decode((string)$row['presentation'],true);return array('id'=>$row['id'],'type'=>$row['nodetype'],'title'=>$row['title'],'description'=>$row['description'],'presentation'=>is_array($presentation)?$presentation:array(),'order'=>(int)$row['sortorder']);},$this->nodes->forMap($id));$relationships=array_map(function($row){$properties=json_decode((string)$row['properties'],true);return array('id'=>$row['id'],'type'=>$row['relationshiptype'],'from'=>$row['fromnodeid'],'to'=>$row['tonodeid'],'externalTarget'=>$row['externaltarget'],'properties'=>is_array($properties)?$properties:array(),'order'=>(int)$row['sortorder']);},$this->relationships->forMap($id));return array('model'=>'chisimba_active_knowledge_map','dataVersion'=>1,'map'=>array('id'=>$map['id'],'title'=>$map['title'],'description'=>$map['description'],'scopeType'=>$map['scopetype'],'scopeId'=>$map['scopeid'],'revision'=>(int)$map['revision']),'rootId'=>$map['rootnodeid'],'nodes'=>$nodes,'relationships'=>$relationships);}

    /** Replace one editable graph atomically after validating its expected revision. */
    public function saveDocument($id,$revision,array $document){$map=$this->map($id,'edit');if(!$map)throw new RuntimeException('Permission denied.');$normal=$this->graph->normalise($document);if(!$normal['valid'])throw new InvalidArgumentException(implode(' ',$normal['errors']));$graph=$normal['document'];$root=null;foreach($graph['nodes'] as $node)if($node['id']===$graph['rootId']){$root=$node;break;}if(!$root)throw new InvalidArgumentException('The graph has no root node.');$this->maps->beginTransaction();try{if(!$this->maps->lockAtRevision($id,$revision))throw new DomainException('This map changed in another session. Reload before saving again.');$this->relationships->removeForMap($id);$this->nodes->removeForMap($id);foreach($graph['nodes'] as $node)$this->nodes->addNode($id,$node);foreach($graph['relationships'] as $relationship)$this->relationships->addRelationship($id,$relationship);$this->maps->updateAtRevision($id,$revision,array('rootnodeid'=>$graph['rootId'],'title'=>$root['title'],'description'=>(string)($document['map']['description']??$map['description'])));$this->maps->commitTransaction();return array('revision'=>$revision+1,'warnings'=>$normal['warnings']);}catch(Throwable $error){$this->maps->rollbackTransaction();throw $error;}}
}
?>
