<?php
/**
 * Controller for the Active Knowledge Map module.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Coordinates scoped map discovery, creation, migration, sharing and viewing. */
class knowledgemap extends controller
{
    const CSRF='knowledgemap_mutation';
    private $user;
    private $maps;
    private $nodes;
    private $relationships;
    private $access;
    private $scope;
    private $authorization;
    private $service;
    private $importer;
    private $csrf;

    /** Initialise application services. */
    public function init(){$this->user=$this->getObject('user','security');$this->maps=$this->getObject('dbknowledgemaps');$this->nodes=$this->getObject('dbknowledgemapnodes');$this->relationships=$this->getObject('dbknowledgemaprelationships');$this->access=$this->getObject('dbknowledgemapaccess');$this->scope=$this->getObject('knowledgemapscopeservice');$this->authorization=$this->getObject('knowledgemapauthorizationservice');$this->service=$this->getObject('knowledgemapservice');$this->importer=$this->getObject('knowledgemapimportservice');$this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];$this->appendArrayVar('headerParams','<link rel="stylesheet" type="text/css" href="'.$this->getResourceUri('knowledgemap.css').'?v=15" />');$this->appendArrayVar('headerParams','<script defer type="text/javascript" src="'.$this->getResourceUri('knowledgemap.js').'?v=13"></script>');}

    /** Require an authenticated user for map discovery and rendering. */
    public function requiresLogin($action){return true;}

    /** Dispatch the requested module action. */
    public function dispatch($action){$action=(string)$action;if(in_array($action,array('create','import','share','save'),true))return $this->{$action}();if($action==='view')return $this->view();return $this->index();}

    /** Render maps visible in the requested scope and maps shared directly with the user. */
    private function index($message='',$error=''){$scope=$this->scope->resolve($this->param('scope'));$rows=$scope['type']==='personal'?array_merge($this->maps->ownedBy($this->user->userId()),$this->maps->sharedWith($this->user->userId())):$this->maps->inScope($scope['type'],$scope['id']);$rows=array_values(array_reduce(array_filter($rows,fn($map)=>$this->authorization->allows($map,'view')),function($carry,$map){$map['permission']=$this->authorization->allows($map,'manage')?'manage':($this->authorization->allows($map,'edit')?'edit':'view');$carry[$map['id']]=$map;return $carry;},array()));$this->setVar('knowledgeMaps',$rows);$this->setVar('knowledgeMapScope',$scope);$this->setVar('knowledgeMapCanCreate',$this->authorization->canCreate($scope['type'],$scope['id']));$this->setVar('knowledgeMapCsrf',$this->csrf->issue(self::CSRF));$this->setVar('knowledgeMapMessage',$message);$this->setVar('knowledgeMapError',$error);return 'index_tpl.php';}

    /** Create a rooted starter graph in one transaction. */
    private function create(){if(!$this->validPost())return $this->index('','Your session expired. Please try again.');$scope=$this->scope->resolve($this->param('scope'));if(!$this->authorization->canCreate($scope['type'],$scope['id']))return $this->forbidden();$title=mb_substr($this->param('title'),0,255);if($title==='')return $this->index('','A map title is required.');$this->maps->beginTransaction();try{$root='node-'.bin2hex(random_bytes(16));$mapId=$this->maps->createMap(array('scopetype'=>$scope['type'],'scopeid'=>$scope['id'],'ownerid'=>$this->user->userId(),'rootnodeid'=>$root,'title'=>$title,'description'=>mb_substr($this->param('description'),0,10000)));$this->nodes->addNode($mapId,array('id'=>$root,'type'=>'standard','title'=>$title,'description'=>'','presentation'=>array('side'=>'root','color'=>'var(--chisimba-surface)'),'order'=>0));$this->maps->commitTransaction();return $this->redirectToView($mapId);}catch(Throwable $error){$this->maps->rollbackTransaction();return $this->index('','The map could not be created.');}}

    /** Preview or transactionally import a Kenga Knowledge Map v3 JSON file. */
    private function import(){if(!$this->validPost())return $this->index('','Your session expired. Please try again.');$scope=$this->scope->resolve($this->param('scope'));if(!$this->authorization->canCreate($scope['type'],$scope['id']))return $this->forbidden();$confirm=$this->param('confirm_import')==='1';if($confirm){$json=base64_decode($this->param('import_payload'),true);if($json===false||strlen($json)>10485760)return $this->index('','The import preview expired. Please choose the file again.');}else{$upload=$_FILES['mapfile']??null;if(!$upload||($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||($upload['size']??0)>10485760)return $this->index('','Choose a Kenga JSON export smaller than 10 MB.');$json=file_get_contents($upload['tmp_name']);}try{$preview=$this->importer->preview($json);if(!$preview['valid'])return $this->index('',implode(' ',$preview['errors']));if(!$confirm){$this->setVar('knowledgeMapImportPreview',$preview);$this->setVar('knowledgeMapImportPayload',base64_encode($json));return $this->index('Import validated. Review the preview before creating the map.');}$mapId=$this->importer->import($json,$scope,$this->user->userId());return $this->redirectToView($mapId);}catch(Throwable $error){return $this->index('',$error->getMessage());}}

    /** Replace invited-user permissions for a map managed by the current user. */
    private function share(){if(!$this->validPost())return $this->index('','Your session expired.');$map=$this->service->map($this->id('mapid'),'manage');if(!$map)return $this->forbidden();$grants=array();foreach(preg_split('/\r?\n/',$this->param('grants')) as $line){$parts=array_map('trim',explode(':',$line,2));if(count($parts)!==2||!preg_match('/^[A-Za-z0-9._@-]{1,191}$/',$parts[0])||!in_array($parts[1],array('view','edit','manage'),true))continue;$userId=$this->authorization->invitedUserId($parts[0]);if($userId&&$userId!==(string)$map['ownerid'])$grants[$userId]=$parts[1];}$this->access->replaceUserGrants($map['id'],$grants,$this->user->userId());return $this->view('Sharing updated.');}

    /** Persist an editor graph with optimistic revision protection. */
    private function save(){if(!$this->validPost())return $this->json(false,'Your session expired.',403);$mapId=$this->id('mapid');$raw=$this->param('document');try{$document=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$result=$this->service->saveDocument($mapId,(int)$this->param('revision'),$document);return $this->json(true,'Map saved.',200,$result);}catch(DomainException $error){return $this->json(false,$error->getMessage(),409);}catch(Throwable $error){return $this->json(false,$error->getMessage(),400);}}

    /** Render the common read-only projection and management surfaces. */
    private function view($message=''){$mapId=$this->id('mapid');$document=$this->service->document($mapId,'view');if(!$document)return $this->forbidden();$map=$this->service->map($mapId,'view');$this->setVar('knowledgeMapDocument',$document);$this->setVar('knowledgeMapRecord',$map);$this->setVar('knowledgeMapCanEdit',$this->authorization->allows($map,'edit'));$this->setVar('knowledgeMapCanManage',$this->authorization->allows($map,'manage'));$this->setVar('knowledgeMapGrants',$this->access->grants($mapId));$this->setVar('knowledgeMapCsrf',$this->csrf->issue(self::CSRF));$this->setVar('knowledgeMapMessage',$message);return 'view_tpl.php';}

    /** Redirect to a newly created or imported map. */
    private function redirectToView($mapId){header('Location: '.html_entity_decode($this->uri(array('action'=>'view','mapid'=>$mapId),'knowledgemap'),ENT_QUOTES,'UTF-8'));exit;}

    /** Validate the native CSRF token on a POST request. */
    private function validPost(){return strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='POST'&&$this->csrf->consume(self::CSRF,$this->param('csrf_token'));}

    /** Return a permission-denied page without leaking map existence. */
    private function forbidden(){http_response_code(403);return 'noaccess_tpl.php';}

    /** Emit a private JSON response and rotate the mutation token. */
    private function json($ok,$message,$status=200,array $data=array()){if(!headers_sent()){header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: private, no-store');http_response_code($status);}echo json_encode(array_merge(array('ok'=>$ok,'message'=>$message,'csrfToken'=>$this->csrf->issue(self::CSRF)),$data),JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);exit;}

    /** Return one trimmed scalar request parameter. */
    private function param($name){$value=$this->getParam($name,'');return is_scalar($value)?trim((string)$value):'';}

    /** Return one valid generated map identifier. */
    private function id($name){$value=$this->param($name);return preg_match('/^[a-f0-9]{32}$/',$value)?$value:'';}
}
?>
