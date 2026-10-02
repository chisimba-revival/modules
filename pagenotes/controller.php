<?php
/**
 * Controller for scoped, attachable notes.
 *
 * @author Derek Keats
 * @package pagenotes
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');

/** Coordinates note discovery, editing, attachment and sharing. */
class pagenotes extends controller
{
 const CSRF='pagenotes_mutation';
 private $user;
 private $notes;
 private $links;
 private $access;
 private $scope;
 private $authorization;
 private $csrf;
 private $richText;

 /** Initialise application services and shared presentation assets. */
 public function init(){$this->user=$this->getObject('user','security');$this->notes=$this->getObject('dbnotes');$this->links=$this->getObject('dbnotelinks');$this->access=$this->getObject('dbnoteaccess');$this->scope=$this->getObject('notescopeservice');$this->authorization=$this->getObject('noteauthorizationservice');$this->richText=$this->getObject('richtextsanitizer','utilities');$this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];$this->setLayoutTemplate('notes_layout_tpl.php');}

 /** Dispatch only known Notes actions. */
 public function dispatch($action){$action=(string)$action;if(in_array($action,array('create','save','attach','detach','share','archive'),true))return $this->{$action}();if($action==='view')return $this->view();return $this->index();}

 /** Render notes visible in the selected scope. */
 private function index($message='',$error=''){$scope=$this->scope->resolve($this->param('scope'),$this->param('scopeid'));$archived=$this->boolParam('archived');$rows=$scope['type']==='personal'?array_merge($this->notes->ownedBy($this->user->userId(),$archived),$this->notes->sharedWith($this->user->userId(),$archived)):$this->notes->inScope($scope['type'],$scope['id'],$archived);$rows=array_values(array_reduce(array_filter($rows,fn($note)=>$this->authorization->allows($note,'view')),function($carry,$note){$note['linkcount']=count($this->links->forNote($note['id']));$carry[$note['id']]=$note;return $carry;},array()));$this->setVar('notesRows',$rows);$this->setVar('notesScope',$scope);$this->setVar('notesCanCreate',$this->authorization->canCreate($scope['type'],$scope['id']));$this->setVar('notesCsrf',$this->csrf->issueForSession(self::CSRF));$this->setVar('notesActor',(string)$this->user->userId());$this->setVar('notesMessage',$message);$this->setVar('notesError',$error);return 'index_tpl.php';}

 /** Create one canonical note in the requested scope. */
 private function create(){if(!$this->validPost())return $this->index('','The form could not be verified. Please try again.');$scope=$this->scope->resolve($this->param('scope'),$this->param('scopeid'));if(!$this->authorization->canCreate($scope['type'],$scope['id']))return $this->forbidden();$title=$this->limited('title',255);if($title==='')return $this->index('','A note title is required.');$id=$this->notes->createNote(array('scopetype'=>$scope['type'],'scopeid'=>$scope['id'],'ownerid'=>$this->user->userId(),'title'=>$title,'body'=>$this->cleanBody()));if(!$id)return $this->index('','The note could not be created. Your text has not been cleared.');return $this->redirectToNote($id);}

 /** Render one note with its backlinks and access controls. */
 private function view($message='',$error=''){$note=$this->note('view');if(!$note)return $this->forbidden();$note['body']=$this->richText->cleanHtml((string)$note['body']);$this->setVar('noteRecord',$note);$this->setVar('noteLinks',$this->links->forNote($note['id']));$this->setVar('noteGrants',$this->access->grants($note['id']));$this->setVar('noteCanEdit',$this->authorization->allows($note,'edit'));$this->setVar('noteCanManage',$this->authorization->allows($note,'manage'));$this->setVar('notesReturnUrl',$this->kanbanReturn());$this->setVar('notesCsrf',$this->csrf->issueForSession(self::CSRF));$this->setVar('notesActor',(string)$this->user->userId());$this->setVar('notesMessage',$message);$this->setVar('notesError',$error);return 'view_tpl.php';}

 /** Save note text and return JSON to asynchronous editors. */
 private function save(){if(!$this->validPost())return $this->respond(false,'The form could not be verified. Your text is kept.',403);$note=$this->note('edit');if(!$note)return $this->respond(false,'You do not have permission to edit this note.',403);$title=$this->limited('title',255);if($title==='')return $this->respond(false,'A note title is required.',422);$saved=$this->notes->saveNote($note['id'],array('title'=>$title,'body'=>$this->cleanBody()));return $this->respond($saved,$saved?'Note saved.':'The note could not be saved. Your text is kept.',$saved?200:500,array('title'=>$title));}

 /** Attach the note to one stable typed Chisimba target. */
 private function attach(){if(!$this->validPost())return $this->respond(false,'The form could not be verified.',403);$note=$this->note('edit');if(!$note)return $this->respond(false,'You do not have permission to attach this note.',403);$type=$this->limited('targettype',48);$id=$this->limited('targetid',255);$label=$this->limited('targetlabel',255);$url=$this->safeUrl($this->limited('targeturl',1000));if(!preg_match('/^[a-z][a-z0-9_]{1,47}$/',$type)||$id===''||$label==='')return $this->respond(false,'Choose a valid item to attach.',422);$linkId=$this->links->addLink($note['id'],$type,$id,$label,$url,$this->user->userId());if(!$linkId)return $this->respond(false,'The attachment could not be saved.',500);return $this->respond(true,'Note attached.',200,array('link'=>array('id'=>$linkId,'targettype'=>$type,'targetid'=>$id,'targetlabel'=>$label,'targeturl'=>$url)));}

 /** Detach a reference without deleting either resource. */
 private function detach(){if(!$this->validPost())return $this->respond(false,'The form could not be verified.',403);$note=$this->note('edit');if(!$note)return $this->respond(false,'You do not have permission to detach this note.',403);$saved=$this->links->removeLink($this->id('linkid'),$note['id']);return $this->respond($saved,$saved?'Attachment removed.':'The attachment could not be removed.',$saved?200:404);}

 /** Replace direct-user grants while retaining future principal types. */
 private function share(){if(!$this->validPost())return $this->view('','The form could not be verified.');$note=$this->note('manage');if(!$note)return $this->forbidden();$grants=array();foreach(preg_split('/\r?\n/',$this->limited('grants',10000)) as $line){$parts=array_map('trim',explode(':',$line,2));if(count($parts)!==2||!preg_match('/^[A-Za-z0-9._@-]{1,191}$/',$parts[0])||!in_array($parts[1],array('view','edit','manage'),true))continue;$userId=$this->user->getUserId($parts[0]);if($userId&&(string)$userId!==(string)$note['ownerid'])$grants[(string)$userId]=$parts[1];}$this->access->replaceUserGrants($note['id'],$grants,$this->user->userId());return $this->view('Sharing updated.');}

 /** Toggle archive state without destroying note history or links. */
 private function archive(){if(!$this->validPost())return $this->view('','The form could not be verified.');$note=$this->note('manage');if(!$note)return $this->forbidden();$this->notes->saveNote($note['id'],array('isarchived'=>empty($note['isarchived'])?1:0));return $this->redirectToIndex($note);}

 /** Return one authorized note. */
 private function note($permission){$note=$this->notes->one($this->id('noteid'));return $note&&$this->authorization->allows($note,$permission)?$note:false;}
 /** Validate a state-changing request. */
 private function validPost(){return strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='POST'&&$this->param('actor')===(string)$this->user->userId()&&$this->csrf->consume(self::CSRF,$this->param('csrf_token'));}
 /** Emit JSON for Ajax or render the note as a progressive fallback. */
 private function respond($ok,$message,$status=200,array $extra=array()){if(strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))!=='xmlhttprequest')return $this->view($ok?$message:'',$ok?'':$message);if(!headers_sent()){header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: private, no-store');http_response_code($status);}echo json_encode(array_merge($extra,array('ok'=>$ok,'message'=>$message,'csrfToken'=>$this->csrf->issueForSession(self::CSRF))),JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
 /** Return a permission-denied surface without leaking note existence. */
 private function forbidden(){http_response_code(403);return 'noaccess_tpl.php';}
 /** Redirect to a note after creation. */
 private function redirectToNote($id){header('Location: '.html_entity_decode($this->uri(array('action'=>'view','noteid'=>$id),'pagenotes'),ENT_QUOTES,'UTF-8'));exit;}
 /** Redirect back to the note's own scope. */
 private function redirectToIndex(array $note){header('Location: '.html_entity_decode($this->uri(array('scope'=>$note['scopetype'],'scopeid'=>$note['scopeid']),'pagenotes'),ENT_QUOTES,'UTF-8'));exit;}
 /** Read one trimmed scalar request value. */
 private function param($name){$value=$this->getParam($name,'');return is_scalar($value)?trim((string)$value):'';}
 /** Read and bound one text value. */
 private function limited($name,$length){return mb_substr($this->param($name),0,$length,'UTF-8');}
 /** Read one generated identifier. */
 private function id($name){$value=$this->param($name);return preg_match('/^[a-f0-9]{32}$/',$value)?$value:'';}
 /** Read one conventional boolean. */
 private function boolParam($name){return in_array(strtolower($this->param($name)),array('1','true','yes','on'),true);}
 /** Permit local or explicit HTTP links, never executable schemes. */
 private function safeUrl($url){if($url==='')return '';$parts=parse_url($url);if($parts===false)return '';if(isset($parts['scheme'])&&!in_array(strtolower($parts['scheme']),array('http','https'),true))return '';return $url;}
 /** Sanitize bounded authored HTML before persistence. */
 private function cleanBody(){return $this->richText->cleanHtml($this->limited('body',50000));}
 /** Accept only an internal return to the Kanban module. */
 private function kanbanReturn(){$value=html_entity_decode($this->limited('return',1200),ENT_QUOTES|ENT_HTML5,'UTF-8');if($value==='')return '';$parts=parse_url($value);if($parts===false||isset($parts['scheme'])||isset($parts['host'])||basename((string)($parts['path']??''))!=='index.php')return '';parse_str((string)($parts['query']??''),$query);return ($query['module']??'')==='kanban'?$value:'';}
}
?>
