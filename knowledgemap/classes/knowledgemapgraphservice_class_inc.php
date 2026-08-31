<?php
/**
 * Graph-domain service for Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Validates graph documents and resolves explicit subgraph scopes. */
class knowledgemapgraphservice extends controller
{
    const RELATIONSHIP_TYPES=array('contains','links_to','related_to','example_of','prerequisite_for','supports','contradicts');

    /** Validate and normalise a graph document without mutating persistence. */
    public function normalise(array $document){
        $errors=array();$warnings=array();$nodes=array();$seenNodes=array();
        foreach((array)($document['nodes']??array()) as $index=>$node){$id=$this->identifier($node['id']??'');if($id===''||isset($seenNodes[$id])){$errors[]='Node '.($index+1).' has a missing or duplicate identifier.';continue;}$seenNodes[$id]=true;$nodes[]=array('id'=>$id,'type'=>$this->nodeType($node['type']??'standard'),'title'=>$this->text($node['title']??'Untitled',255),'description'=>$this->text($node['description']??'',100000),'presentation'=>$this->presentation($node),'order'=>(int)($node['order']??$index));}
        if(!$nodes)$errors[]='The map contains no nodes.';
        $rootId=$this->identifier($document['rootId']??'');if($rootId===''||!isset($seenNodes[$rootId]))$errors[]='The root node does not exist.';
        $relationships=array();$seenRelationships=array();$containsParent=array();
        foreach((array)($document['relationships']??array()) as $index=>$relationship){$type=strtolower($this->identifier($relationship['type']??''));$from=$this->identifier($relationship['from']??'');$to=$this->identifier($relationship['to']??'');$external='';if($type==='links_to'&&!isset($seenNodes[$to])){$external=$this->externalTarget($relationship['externalTarget']??($relationship['to']??''));$to='';}if(!in_array($type,self::RELATIONSHIP_TYPES,true)||!isset($seenNodes[$from])||($to!==''&&!isset($seenNodes[$to]))){$errors[]='Relationship '.($index+1).' has an invalid type or endpoint.';continue;}if(($type!=='links_to'&&$to==='')||($type==='links_to'&&$to===''&&$external==='')){$errors[]='Relationship '.($index+1).' requires a target.';continue;}if($type==='contains'){if($from===$to){$errors[]='A node cannot contain itself.';continue;}if(isset($containsParent[$to])){$errors[]='A node cannot have two containment parents.';continue;}$containsParent[$to]=$from;}$id=$this->identifier($relationship['id']??'');if($id==='')$id='rel-'.substr(hash('sha256',$type.'|'.$from.'|'.$to.'|'.$external),0,60);if(isset($seenRelationships[$id])){$errors[]='Relationship '.($index+1).' has a duplicate identifier.';continue;}$seenRelationships[$id]=true;$relationships[]=array('id'=>$id,'type'=>$type,'from'=>$from,'to'=>$to,'externalTarget'=>$external,'properties'=>$this->properties($relationship['properties']??array()),'order'=>(int)($relationship['order']??$index));}
        if($this->containsCycle($containsParent))$errors[]='Containment relationships contain a cycle.';
        foreach($nodes as $node)if($node['id']!==$rootId&&!isset($containsParent[$node['id']]))$warnings[]='Node “'.$node['title'].'” is outside the rooted containment view.';
        return array('valid'=>!$errors,'errors'=>$errors,'warnings'=>$warnings,'document'=>array('model'=>'chisimba_active_knowledge_map','dataVersion'=>1,'rootId'=>$rootId,'metadata'=>is_array($document['metadata']??null)?$document['metadata']:array(),'nodes'=>$nodes,'relationships'=>$relationships));
    }

    /** Resolve a frozen node, descendants or whole-map snapshot. */
    public function subgraph(array $document,$nodeId,$scope){$normal=$this->normalise($document);if(!$normal['valid'])throw new InvalidArgumentException('Cannot scope an invalid graph.');$graph=$normal['document'];if($scope==='whole_map')return $graph;$wanted=array($nodeId=>true);if($scope==='descendants'){$changed=true;while($changed){$changed=false;foreach($graph['relationships'] as $rel)if($rel['type']==='contains'&&isset($wanted[$rel['from']])&&!isset($wanted[$rel['to']])){$wanted[$rel['to']]=true;$changed=true;}}}elseif($scope!=='node')throw new InvalidArgumentException('Unsupported graph scope.');$graph['nodes']=array_values(array_filter($graph['nodes'],fn($node)=>isset($wanted[$node['id']])));$graph['relationships']=array_values(array_filter($graph['relationships'],fn($rel)=>isset($wanted[$rel['from']])&&($rel['to']===''||isset($wanted[$rel['to']]))));$graph['rootId']=$nodeId;return $graph;}

    /** Convert safe presentation properties from an imported node. */
    private function presentation(array $node){$allowed=array('color','icon','fontColor','fontSize','x','y','side','dir','collapsed');$source=is_array($node['presentation']??null)?array_merge($node,$node['presentation']):$node;$out=array();foreach($allowed as $key)if(isset($source[$key])&&(is_scalar($source[$key])||is_bool($source[$key])))$out[$key]=$source[$key];return $out;}

    /** Retain scalar relationship properties only. */
    private function properties($properties){$out=array();if(!is_array($properties))return $out;foreach($properties as $key=>$value)if(preg_match('/^[a-z][a-z0-9_]{0,63}$/i',(string)$key)&&is_scalar($value))$out[$key]=mb_substr((string)$value,0,1000);return $out;}

    /** Detect cycles in the single-parent containment projection. */
    private function containsCycle(array $parentFor){foreach(array_keys($parentFor) as $start){$seen=array();$node=$start;while(isset($parentFor[$node])){if(isset($seen[$node]))return true;$seen[$node]=true;$node=$parentFor[$node];}}return false;}

    /** Normalise a safe opaque graph identifier. */
    private function identifier($value){$value=trim((string)$value);return preg_match('/^[A-Za-z0-9._:-]{1,64}$/',$value)?$value:'';}

    /** Normalise a supported node type. */
    private function nodeType($value){$value=strtolower($this->identifier($value));return in_array($value,array('standard','folder','reference'),true)?$value:'standard';}

    /** Bound imported text without interpreting markup. */
    private function text($value,$length){return mb_substr(trim((string)$value),0,$length);}

    /** Normalise a URL-like external target. */
    private function externalTarget($value){$value=trim((string)$value);if($value==='')return '';if(!preg_match('~^(https?://|/|#|mailto:|tel:)~i',$value))$value='https://'.$value;return mb_substr($value,0,4000);}
}
?>
