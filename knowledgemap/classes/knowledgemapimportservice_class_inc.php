<?php
/**
 * Kenga Knowledge Map migration service.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Previews and transactionally imports Kenga v3 graph documents. */
class knowledgemapimportservice extends controller
{
    private $maps;
    private $nodes;
    private $relationships;
    private $graph;

    /** Initialise migration dependencies. */
    public function init(){$this->maps=$this->getObject('dbknowledgemaps');$this->nodes=$this->getObject('dbknowledgemapnodes');$this->relationships=$this->getObject('dbknowledgemaprelationships');$this->graph=$this->getObject('knowledgemapgraphservice');}

    /** Parse an uploaded Kenga JSON document and return a non-mutating preview. */
    public function preview($json){$fingerprint=hash('sha256',(string)$json);try{$source=json_decode((string)$json,true,512,JSON_THROW_ON_ERROR);$converted=$this->convert($source);}catch(Throwable $error){return array('valid'=>false,'errors'=>array($error instanceof JsonException?'The file is not valid JSON.':$error->getMessage()),'warnings'=>array(),'fingerprint'=>$fingerprint);}$result=$this->graph->normalise($converted);$result['fingerprint']=$fingerprint;$result['duplicate']=false;$result['sourceFormat']='kenga_knowledge_document_v3';$result['counts']=array('nodes'=>count($result['document']['nodes']??array()),'relationships'=>count($result['document']['relationships']??array()));return $result;}

    /** Import a previously previewed source as one atomic graph. */
    public function import($json,array $scope,$ownerId){$preview=$this->preview($json);if(!$preview['valid'])throw new InvalidArgumentException(implode(' ',$preview['errors']));if($this->maps->bySourceFingerprint($preview['fingerprint'],$scope['type'],$scope['id'],$ownerId))throw new DomainException('This source has already been imported into this scope.');$document=$preview['document'];$title=trim((string)($document['metadata']['title']??''));if($title==='')foreach($document['nodes'] as $node)if($node['id']===$document['rootId']){$title=$node['title'];break;}if($title==='')$title='Imported knowledge map';$this->maps->beginTransaction();try{$mapId=$this->maps->createMap(array('scopetype'=>$scope['type'],'scopeid'=>$scope['id'],'ownerid'=>$ownerId,'rootnodeid'=>$document['rootId'],'title'=>mb_substr($title,0,255),'description'=>mb_substr((string)($document['metadata']['description']??''),0,10000),'sourceformat'=>$preview['sourceFormat'],'sourcefingerprint'=>$preview['fingerprint'],'sourcemetadata'=>json_encode(array('importedAt'=>date(DATE_ATOM),'sourceVersion'=>3,'warnings'=>$preview['warnings']),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)));if(!$mapId)throw new RuntimeException('The map record could not be created.');foreach($document['nodes'] as $node)if(!$this->nodes->addNode($mapId,$node))throw new RuntimeException('Node “'.$node['title'].'” could not be imported.');foreach($document['relationships'] as $relationship)if(!$this->relationships->addRelationship($mapId,$relationship))throw new RuntimeException('A graph relationship could not be imported.');$this->maps->commitTransaction();return $mapId;}catch(Throwable $error){$this->maps->rollbackTransaction();throw $error;}}

    /** Convert the Kenga v3 compatibility cache into explicit relationships. */
    private function convert(array $source){if(($source['model']??'')!=='kenga_knowledge_document'||(int)($source['dataVersion']??0)!==3)throw new InvalidArgumentException('Only Kenga Knowledge Map v3 JSON is supported.');$relationships=is_array($source['relationships']??null)?$source['relationships']:array();if(!$relationships){foreach((array)($source['nodes']??array()) as $node){if(!empty($node['parentId']))$relationships[]=array('type'=>'contains','from'=>$node['parentId'],'to'=>$node['id'],'properties'=>array());if(!empty($node['url']))$relationships[]=array('type'=>'links_to','from'=>$node['id'],'to'=>$node['url'],'properties'=>array('target'=>'_blank'));}}$source['relationships']=$relationships;return $source;}
}
?>
